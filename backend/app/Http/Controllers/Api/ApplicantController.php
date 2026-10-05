<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicantStatus;
use App\Enums\WorkflowAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Applicant\StoreApplicantRequest;
use App\Http\Resources\Applicant\ApplicantResource;
use App\Models\Applicant;
use App\Models\Branch;
use App\Models\Generation;
use App\Models\Team;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\ID\ConfidentialIdGenerator;
use App\Services\ID\IdSequenceService;
use App\Services\ID\OfficialIdGenerator;
use App\Services\Organization\OrganizationCapacityService;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ApplicantController — full applicant onboarding workflow.
 *
 * Spec §13–§19.
 *
 * Workflow steps (config/workflows.php → 'applicant_onboarding'):
 *   1. Submitted (applicant)        → submit application
 *   2. Team Leader Screening        → team_leader: screen
 *   3. Branch Assignment             → branch_leader: assign (capacity-validated)
 *   4. Record Verification           → record_officer: generate IDs
 *   5. Account Creation              → system_admin: create account + temp password
 *   6. Approved                      → user notified + forced password change on first login
 *
 * Every action is transactional, audited, and dispatches a DB notification
 * to the next actor in the chain.
 */
class ApplicantController extends Controller
{
    public function __construct(
        private WorkflowEngine $workflows,
        private AuditLogger $audit,
        private OrganizationCapacityService $capacity,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Applicant::class);

        $applicants = Applicant::query()
            ->visibleTo($request->user())
            ->with(['assignedGeneration', 'assignedBranch', 'assignedTeam'])
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        return ApplicantResource::collection($applicants);
    }

    /**
     * Public self-signup — spec §14.
     * Creates the applicant + starts the workflow.
     */
    public function apply(StoreApplicantRequest $request, IdSequenceService $sequences)
    {
        $applicant = DB::transaction(function () use ($request, $sequences) {
            $year = now()->year;
            $key  = "app:{$year}";
            $sequences->ensureExists($key, 'APP', 6);
            $seqValue = $sequences->next($key);
            $code = "APP-{$year}-" . str_pad((string) $seqValue, 6, '0', STR_PAD_LEFT);

            $applicant = Applicant::create(array_merge($request->validated(), [
                'application_code'    => $code,
                'status'              => ApplicantStatus::Submitted,
                'terms_accepted_at'   => now(),
            ]));

            // Start the workflow — creatorId is NULL (public, no authenticated user)
            $applicant->startWorkflow('applicant_onboarding', creatorId: null);

            $this->audit->log(
                action: 'applicant.submit',
                entityType: Applicant::class,
                entityId: $applicant->id,
                category: 'workflow',
                newValues: ['application_code' => $code],
            );

            // Notify all Team Leaders that a new applicant is awaiting screening
            $this->notifyRole('team_leader', 'New applicant awaiting screening', [
                'applicant_id'  => $applicant->id,
                'application_code' => $applicant->application_code,
                'full_name'       => $applicant->full_name,
            ]);

            return $applicant;
        });

        return response()->json([
            'message'    => 'Application submitted. A Team Leader will be in touch shortly.',
            'applicant' => new ApplicantResource($applicant->fresh(['assignedGeneration', 'assignedBranch', 'assignedTeam'])),
        ], 201);
    }

    public function show(Request $request, Applicant $applicant)
    {
        $this->authorize('view', $applicant);
        $applicant->load(['documents', 'reviews', 'assignments', 'assignedBranch', 'assignedTeam', 'assignedGeneration']);
        return new ApplicantResource($applicant);
    }

    /**
     * Step 2 — Team Leader screens the applicant (spec §15).
     * Decision: approve (forward to Branch Leader) / reject / correction_required.
     */
    public function screen(Request $request, Applicant $applicant)
    {
        $this->authorize('screen', $applicant);

        // Validate workflow is at the right step
        $this->ensureStatus($applicant, [
            ApplicantStatus::Submitted,
            ApplicantStatus::UnderReview,
            ApplicantStatus::TeamLeaderScreening,
        ]);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject,correction_required'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($applicant, $data, $request) {
            $applicant->reviews()->create([
                'reviewer_user_id' => $request->user()->id,
                'review_stage'     => 'team_screening',
                'decision'         => $data['decision'],
                'comment'          => $data['comment'] ?? null,
            ]);

            $next = match ($data['decision']) {
                'approve' => ApplicantStatus::BranchAssignment,
                'reject' => ApplicantStatus::Rejected,
                'correction_required' => ApplicantStatus::TeamLeaderScreening,
            };

            $applicant->update([
                'reviewed_by_team_leader_id' => $request->user()->id,
                'status'                     => $next,
                'rejected_at'                => $data['decision'] === 'reject' ? now() : null,
                'rejection_reason'            => $data['decision'] === 'reject' ? ($data['comment'] ?? null) : null,
            ]);

            $action = match ($data['decision']) {
                'approve' => WorkflowAction::Approve,
                'reject' => WorkflowAction::Reject,
                'correction_required' => WorkflowAction::RequestCorrection,
            };

            // Advance workflow instance if one exists
            $instance = $applicant->currentWorkflow();
            if ($instance) {
                if ($data['decision'] === 'reject') {
                    $this->workflows->reject($instance, $request->user(), $data['comment'] ?? 'Rejected by Team Leader');
                } else {
                    $this->workflows->advance($instance, $request->user(), $action, $data['comment']);
                }
            }

            $this->audit->log(
                action: "applicant.screen.{$data['decision']}",
                entityType: Applicant::class,
                entityId: $applicant->id,
                category: 'workflow',
                newValues: ['decision' => $data['decision'], 'comment' => $data['comment'] ?? null],
            );

            // Notify next actor (or applicant on rejection)
            if ($data['decision'] === 'approve') {
                $this->notifyRole('branch_leader', 'Applicant awaiting branch assignment', [
                    'applicant_id'  => $applicant->id,
                    'application_code' => $applicant->application_code,
                    'full_name'       => $applicant->full_name,
                ]);
            }
        });

        return new ApplicantResource($applicant->fresh(['reviews', 'assignedBranch', 'assignedTeam']));
    }

    /**
     * Step 3 — Branch Leader assigns generation/branch/team (spec §16).
     * Capacity is validated; rejection if any unit is at full capacity.
     */
    public function assign(Request $request, Applicant $applicant)
    {
        $this->authorize('assignBranch', $applicant);
        $this->ensureStatus($applicant, [ApplicantStatus::BranchAssignment]);

        $data = $request->validate([
            'generation_id' => ['required', 'exists:generations,id'],
            'branch_id'      => ['required', 'exists:branches,id'],
            'team_id'        => ['required', 'exists:teams,id'],
        ]);

        // Validate hierarchy consistency
        $team = Team::with('branch.generation')->findOrFail($data['team_id']);
        if ((int) $team->branch_id !== (int) $data['branch_id']) {
            throw ValidationException::withMessages([
                'team_id' => 'The selected team does not belong to the selected branch.',
            ]);
        }
        if ((int) $team->branch->generation_id !== (int) $data['generation_id']) {
            throw ValidationException::withMessages([
                'branch_id' => 'The selected branch does not belong to the selected generation.',
            ]);
        }

        // Capacity check — team must have room for a new member (spec §16)
        $this->capacity->ensureTeamCanAcceptMember($team);

        DB::transaction(function () use ($applicant, $data, $request, $team) {
            $applicant->update([
                'assigned_branch_leader_id' => $request->user()->id,
                'assigned_generation_id'    => $data['generation_id'],
                'assigned_branch_id'        => $data['branch_id'],
                'assigned_team_id'          => $data['team_id'],
                'status'                    => ApplicantStatus::RecordVerification,
            ]);
            $applicant->assignments()->create([
                'assigner_user_id' => $request->user()->id,
                'generation_id'   => $data['generation_id'],
                'branch_id'       => $data['branch_id'],
                'team_id'         => $data['team_id'],
            ]);

            $instance = $applicant->currentWorkflow();
            if ($instance) {
                $this->workflows->advance($instance, $request->user(), WorkflowAction::Assign, "Assigned to {$team->name}");
            }

            $this->audit->log(
                action: 'applicant.assign',
                entityType: Applicant::class,
                entityId: $applicant->id,
                category: 'workflow',
                newValues: $data,
            );

            // Notify Record Officers
            $this->notifyRole('record_officer', 'Applicant awaiting ID generation', [
                'applicant_id'  => $applicant->id,
                'application_code' => $applicant->application_code,
                'full_name'       => $applicant->full_name,
                'assigned_team'   => $team->name,
            ]);
        });

        return new ApplicantResource($applicant->fresh(['assignedBranch', 'assignedTeam', 'assignedGeneration']));
    }

    /**
     * Step 4 — Record Officer generates Official ID + Confidential ID (spec §17).
     */
    public function generateIds(
        Request $request,
        Applicant $applicant,
        OfficialIdGenerator $officialGenerator,
        ConfidentialIdGenerator $confidentialGenerator,
    ) {
        $this->authorize('generateIds', $applicant);
        $this->ensureStatus($applicant, [ApplicantStatus::RecordVerification]);

        abort_if(!$applicant->assigned_team_id, 422, 'Applicant must be assigned to a team before ID generation (spec §17).');

        $year = $applicant->created_at->year ?? now()->year;
        $team = Team::with('branch')->findOrFail($applicant->assigned_team_id);

        DB::transaction(function () use ($applicant, $request, $officialGenerator, $confidentialGenerator, $year, $team) {
            $officialId = $officialGenerator->forApplicant($applicant->id);

            $confidentialId = $confidentialGenerator->forApplicant(
                applicantId: $applicant->id,
                year: $year,
                parts: [
                    'generation' => $team->branch?->generation_id ?? 1,
                    'branch'      => $team->branch?->branch_number ?? 1,
                    'team'        => $team->team_number,
                    'rank'        => 1, // default rank; recalculated later by Performance service
                ],
            );

            $applicant->update([
                'generated_official_id'         => $officialId,
                'generated_confidential_id'     => encrypt($confidentialId),
                'status'                         => ApplicantStatus::AccountCreation,
                'record_officer_id'              => $request->user()->id,
            ]);

            $instance = $applicant->currentWorkflow();
            if ($instance) {
                $this->workflows->advance($instance, $request->user(), WorkflowAction::Generate, "Generated Official ID: {$officialId}");
            }

            $this->audit->log(
                action: 'applicant.generate_ids',
                entityType: Applicant::class,
                entityId: $applicant->id,
                category: 'identity',
                newValues: ['official_id' => $officialId],
            );

            // Notify System Administrators
            $this->notifyRole('system_administrator', 'Applicant awaiting account creation', [
                'applicant_id'  => $applicant->id,
                'application_code' => $applicant->application_code,
                'official_id'    => $officialId,
                'full_name'       => $applicant->full_name,
            ]);
        });

        return response()->json([
            'message'     => 'IDs generated. Awaiting account creation by System Administrator.',
            'official_id' => $applicant->fresh()->generated_official_id,
            'applicant'   => new ApplicantResource($applicant->fresh()),
        ]);
    }

    /**
     * Step 5 — System Administrator creates the user account (spec §18, §19).
     * Temp password is cryptographically secure, hashed, one-time use.
     */
    public function createAccount(Request $request, Applicant $applicant)
    {
        $this->authorize('createAccount', $applicant);
        $this->ensureStatus($applicant, [ApplicantStatus::AccountCreation]);
        abort_if(!$applicant->generated_official_id, 422, 'IDs must be generated first (spec §17).');

        $data = $request->validate([
            'username' => ['required', 'string', 'max:64', 'unique:users,username'],
            'email'    => ['required', 'email:rfc,dns', 'max:191', 'unique:users,email'],
        ]);

        // Generate a cryptographically secure temp password (spec §19)
        $tempPassword = Str::random(24);

        $user = DB::transaction(function () use ($applicant, $data, $tempPassword) {
            $user = User::create([
                'official_id'            => $applicant->generated_official_id,
                'confidential_id'        => $applicant->generated_confidential_id, // already encrypted
                'username'               => $data['username'],
                'email'                  => $data['email'],
                'password'               => $tempPassword, // hashed via model cast
                'must_change_password'  => true,           // spec §19 — forced on first login
                'is_active'              => true,
                'level'                  => 1,              // start at Level 1 — Team Member
            ]);
            $user->assignRole('team_member');

            // Add to team
            \App\Models\TeamMember::create([
                'user_id'   => $user->id,
                'team_id'   => $applicant->assigned_team_id,
                'level'     => 1,
                'joined_at' => now(),
            ]);
            $user->update(['current_team_id' => $applicant->assigned_team_id]);

            $applicant->update([
                'status'       => ApplicantStatus::Approved,
                'approved_at'  => now(),
            ]);

            $instance = $applicant->currentWorkflow();
            if ($instance) {
                $this->workflows->advance($instance, $request->user(), WorkflowAction::Finalize, "Account created: {$user->username}");
            }

            $this->audit->log(
                action: 'applicant.account_created',
                entityType: Applicant::class,
                entityId: $applicant->id,
                category: 'workflow',
                newValues: ['user_id' => $user->id, 'username' => $user->username, 'official_id' => $user->official_id],
            );

            // Notify the new user (in-app DB notification — email would be sent via Mailable in production)
            $user->notify(new \App\Notifications\ApplicantApproved($applicant, $tempPassword));

            return $user;
        });

        return response()->json([
            'message'        => 'Account created. The applicant will receive credentials via email + in-app notification.',
            'temp_password'  => $tempPassword, // returned ONCE — transmitted to user out-of-band (e.g. email)
            'user'           => new \App\Http\Resources\UserResource($user),
        ], 201);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function ensureStatus(Applicant $applicant, array $allowed): void
    {
        if (!in_array($applicant->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "This action requires the applicant to be in one of these states: " . implode(', ', array_map(fn($s) => $s->value, $allowed)) . ". Current: {$applicant->status->value}.",
            ]);
        }
    }

    /**
     * Send a DB notification to all users with the given role.
     * Used to alert the next workflow actor that work is awaiting them.
     */
    private function notifyRole(string $role, string $title, array $data): void
    {
        $users = User::role($role)->get();
        foreach ($users as $user) {
            $user->notify(new \App\Notifications\WorkflowAdvanceNotification($title, $data));
        }
    }
}
