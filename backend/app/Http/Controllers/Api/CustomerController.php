<?php

namespace App\Http\Controllers\Api;

use App\Enums\CustomerStatus;
use App\Enums\WorkflowAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Resources\Customer\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerDuplicate;
use App\Services\Audit\AuditLogger;
use App\Services\Customer\DuplicateDetector;
use App\Services\ID\CustomerReferenceGenerator;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CustomerController — full customer registration workflow.
 *
 * Spec §8–§9, §41.
 *
 * Workflow steps (config/workflows.php → 'customer_registration'):
 *   1. Draft                          (team_member)  → submit
 *   2. Pending Team Evaluation       (team_leader)  → evaluate (approve / reject / correction_required)
 *   3. Pending Record Approval        (record_officer) → verify + duplicate detection + register
 *   4. Registered                     (final)         → reference code issued
 *
 * Key business rules:
 *   - Duplicate customers are detected before registration (spec §9, §41 rule #6)
 *   - Existing customers retain their original reference code (spec §41 rule #5)
 *   - New customers get a fresh CUS-YYYY-NNNNNN code (spec §9)
 *   - All workflow transitions are transactional + audited
 */
class CustomerController extends Controller
{
    public function __construct(
        private WorkflowEngine $workflows,
        private AuditLogger $audit,
        private DuplicateDetector $duplicates,
        private CustomerReferenceGenerator $refs,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->visibleTo($request->user())
            ->with(['salesAgent', 'team', 'branch', 'generation'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('reference_code', 'like', "%{$search}%")
                      ->orWhere('full_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = DB::transaction(function () use ($request) {
            $user = $request->user();
            $team = $user->teamMember?->team;

            $customer = Customer::create(array_merge($request->validated(), [
                'status'               => CustomerStatus::Draft,
                'sales_agent_user_id' => $user->id,
                'team_id'             => $team?->id,
                'branch_id'           => $team?->branch_id,
                'generation_id'       => $team?->branch?->generation_id,
            ]));

            // Start the customer_registration workflow (spec §8)
            $customer->startWorkflow('customer_registration', creatorId: $user->id);

            $this->audit->log(
                action: 'customer.submit',
                entityType: Customer::class,
                entityId: $customer->id,
                category: 'workflow',
                newValues: ['status' => $customer->status->value, 'sales_agent' => $user->official_id],
            );

            // Notify Team Leader(s) of the team
            $this->notifyTeamLeaders($customer, 'New customer awaiting evaluation');

            return $customer;
        });

        return new CustomerResource($customer->load(['salesAgent', 'team', 'branch', 'generation']));
    }

    public function show(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);
        $customer->load(['salesAgent', 'documents', 'reviews', 'team', 'branch', 'generation']);
        return new CustomerResource($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        $customer->update($request->validate([
            'full_name'       => ['sometimes', 'string', 'max:191'],
            'email'           => ['sometimes', 'nullable', 'email:rfc,dns', 'max:191'],
            'phone'           => ['sometimes', 'string', 'max:32'],
            'notes'           => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]));
        return new CustomerResource($customer->fresh());
    }

    public function destroy(Request $request, Customer $customer)
    {
        $this->authorize('delete', $customer);
        $customer->delete();
        return response()->json(['message' => 'Customer deactivated.']);
    }

    /**
     * Step 2 — Team Leader evaluates the customer (spec §8).
     */
    public function evaluate(Request $request, Customer $customer)
    {
        $this->authorize('evaluate', $customer);
        $this->ensureStatus($customer, [CustomerStatus::Submitted, CustomerStatus::PendingTeamEvaluation]);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject,correction_required'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($customer, $data, $request) {
            $customer->reviews()->create([
                'reviewer_user_id' => $request->user()->id,
                'review_stage'     => 'team_leader',
                'decision'         => $data['decision'],
                'comment'          => $data['comment'] ?? null,
            ]);

            $next = match ($data['decision']) {
                'approve' => CustomerStatus::PendingRecordApproval,
                'reject' => CustomerStatus::Rejected,
                'correction_required' => CustomerStatus::CorrectionRequired,
            };

            $customer->update(['status' => $next]);

            $action = match ($data['decision']) {
                'approve' => WorkflowAction::Approve,
                'reject' => WorkflowAction::Reject,
                'correction_required' => WorkflowAction::RequestCorrection,
            };

            $instance = $customer->currentWorkflow();
            if ($instance) {
                if ($data['decision'] === 'reject') {
                    $this->workflows->reject($instance, $request->user(), $data['comment'] ?? 'Rejected by Team Leader');
                } else {
                    $this->workflows->advance($instance, $request->user(), $action, $data['comment']);
                }
            }

            $this->audit->log(
                action: "customer.evaluate.{$data['decision']}",
                entityType: Customer::class,
                entityId: $customer->id,
                category: 'workflow',
                newValues: ['decision' => $data['decision'], 'comment' => $data['comment'] ?? null],
            );

            if ($data['decision'] === 'approve') {
                $this->notifyRole('record_officer', 'Customer awaiting record approval', [
                    'customer_id' => $customer->id,
                    'reference_code' => $customer->reference_code,
                    'full_name'   => $customer->full_name,
                ]);
            }
        });

        return new CustomerResource($customer->fresh());
    }

    /**
     * Step 3 — Record Officer verifies + checks for duplicates + finalizes registration.
     *
     * Spec §9 + §41 rule #5/#6.
     *
     * If a duplicate is found:
     *   - Customer's status stays 'pending_record_approval' (awaiting resolution)
     *   - Duplicate records are created in customer_duplicates table
     *   - Record Officer is prompted to resolve (kept_existing | created_new)
     *
     * If no duplicates:
     *   - Customer's reference code is generated (NEW) or kept (existing)
     *   - Status advances to 'registered'
     */
    public function approve(Request $request, Customer $customer)
    {
        $this->authorize('approve', $customer);
        $this->ensureStatus($customer, [CustomerStatus::PendingRecordApproval]);

        // Step 3a: Run duplicate detection (spec §9)
        $matches = $this->duplicates->detect($customer);

        if ($matches->isNotEmpty()) {
            // Record duplicates for the Record Officer to resolve
            if ($customer->exists) {
                $this->duplicates->record($customer, $matches, $request->user()->id);
            }

            $this->audit->log(
                action: 'customer.duplicates_detected',
                entityType: Customer::class,
                entityId: $customer->id,
                category: 'workflow',
                severity: \App\Enums\AuditSeverity::Warning,
                newValues: [
                    'duplicate_count' => $matches->count(),
                    'existing_ids'    => $matches->pluck('existing_customer.id')->all(),
                ],
            );

            return response()->json([
                'message'    => 'Potential duplicate customers found. Resolve before finalizing registration.',
                'duplicates' => $matches->map(fn($m) => [
                    'existing_customer_id' => $m['existing_customer']->id,
                    'existing_reference'   => $m['existing_customer']->reference_code,
                    'existing_name'        => $m['existing_customer']->full_name,
                    'match_field'           => $m['match_field'],
                    'match_score'           => $m['match_score'],
                ]),
            ], 409); // 409 Conflict — needs resolution
        }

        // Step 3b: No duplicates → finalize registration + generate reference code
        $this->finalizeRegistration($customer, $request->user(), createdNew: true);

        return new CustomerResource($customer->fresh());
    }

    /**
     * Resolve a duplicate: Record Officer decides 'kept_existing' or 'created_new'.
     *
     * Spec §9 — "If customer already EXISTS: identify existing customer,
     * update existing customer, keep existing reference code."
     */
    public function resolveDuplicate(Request $request, Customer $customer, int $duplicateId)
    {
        $this->authorize('approve', $customer);

        $duplicate = CustomerDuplicate::where('id', $duplicateId)
            ->where('new_customer_id', $customer->id)
            ->firstOrFail();

        $data = $request->validate([
            'action' => ['required', 'in:kept_existing,created_new,merged'],
        ]);

        DB::transaction(function () use ($customer, $duplicate, $data, $request) {
            $this->duplicates->resolve($duplicate, $data['action'], $request->user()->id);

            if ($data['action'] === 'kept_existing') {
                // Spec §41 rule #5 — keep existing reference code
                $existing = Customer::find($duplicate->existing_customer_id);
                $customer->update([
                    'reference_code' => $existing->reference_code,
                    'status'         => CustomerStatus::Registered,
                    'notes'          => 'Merged with existing customer ' . $existing->reference_code,
                ]);

                // Soft-delete the new (duplicate) record so audit history is preserved
                $customer->delete();

                $this->audit->log(
                    action: 'customer.duplicate_resolved.kept_existing',
                    entityType: Customer::class,
                    entityId: $customer->id,
                    category: 'workflow',
                    newValues: ['existing_reference' => $existing->reference_code],
                );
            } else {
                // 'created_new' — proceed with normal registration + fresh reference code
                $this->finalizeRegistration($customer, $request->user(), createdNew: true);

                $this->audit->log(
                    action: 'customer.duplicate_resolved.created_new',
                    entityType: Customer::class,
                    entityId: $customer->id,
                    category: 'workflow',
                    newValues: ['reference_code' => $customer->fresh()->reference_code],
                );
            }
        });

        return new CustomerResource($customer->fresh());
    }

    public function reject(Request $request, Customer $customer)
    {
        $this->authorize('approve', $customer);

        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($customer, $data, $request) {
            $customer->update([
                'status' => CustomerStatus::Rejected,
                'notes'  => $data['reason'],
            ]);

            $instance = $customer->currentWorkflow();
            if ($instance) {
                $this->workflows->reject($instance, $request->user(), $data['reason']);
            }

            $this->audit->log(
                action: 'customer.reject',
                entityType: Customer::class,
                entityId: $customer->id,
                category: 'workflow',
                severity: \App\Enums\AuditSeverity::Warning,
                newValues: ['reason' => $data['reason']],
            );
        });

        return new CustomerResource($customer->fresh());
    }

    // ─── Internals ───────────────────────────────────────────────────────

    private function finalizeRegistration(Customer $customer, $user, bool $createdNew): void
    {
        $referenceCode = $createdNew
            ? $this->refs->next(now()->year, $customer->id, $user->id)
            : $customer->reference_code;

        $customer->update([
            'reference_code' => $referenceCode,
            'status'         => CustomerStatus::Registered,
        ]);

        // Record in customer_references (audit trail of issuance)
        \App\Models\CustomerReference::create([
            'customer_id' => $customer->id,
            'reference_code' => $referenceCode,
            'issued_by' => $user->id,
            'note' => $createdNew ? 'New customer registration' : 'Kept existing reference code',
        ]);

        $instance = $customer->currentWorkflow();
        if ($instance) {
            $this->workflows->advance($instance, $user, WorkflowAction::Finalize, "Registered with code {$referenceCode}");
        }

        $this->audit->log(
            action: 'customer.registered',
            entityType: Customer::class,
            entityId: $customer->id,
            category: 'workflow',
            newValues: ['reference_code' => $referenceCode, 'created_new' => $createdNew],
        );

        $this->notifyRole('team_leader', "Customer registered: {$customer->full_name} ({$referenceCode})", [
            'customer_id' => $customer->id,
            'reference_code' => $referenceCode,
        ]);
    }

    private function ensureStatus(Customer $customer, array $allowed): void
    {
        if (!in_array($customer->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Action requires status: " . implode(', ', array_map(fn($s) => $s->value, $allowed)) . ". Current: {$customer->status->value}.",
            ]);
        }
    }

    private function notifyRole(string $role, string $title, array $data): void
    {
        $users = \App\Models\User::role($role)->get();
        foreach ($users as $user) {
            $user->notify(new \App\Notifications\WorkflowAdvanceNotification($title, $data));
        }
    }

    private function notifyTeamLeaders(Customer $customer, string $title): void
    {
        if (!$customer->team_id) return;
        $team = \App\Models\Team::with('leader')->find($customer->team_id);
        if ($team?->leader) {
            $team->leader->notify(new \App\Notifications\WorkflowAdvanceNotification($title, [
                'customer_id' => $customer->id,
                'reference_code' => $customer->reference_code,
                'full_name' => $customer->full_name,
            ]));
        }
    }
}
