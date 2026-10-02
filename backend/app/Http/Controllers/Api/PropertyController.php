<?php

namespace App\Http\Controllers\Api;

use App\Enums\PropertyStatus;
use App\Enums\WorkflowAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Resources\Property\PropertyResource;
use App\Models\Property;
use App\Models\PropertyAsset;
use App\Models\PropertyPublication;
use App\Services\Audit\AuditLogger;
use App\Services\ID\AssetCodeGenerator;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * PropertyController — full property registration + verification + publication workflow.
 *
 * Spec §10–§12.
 *
 * Workflow steps (config/workflows.php → 'property_registration'):
 *   1. Draft                          (generation_leader) → submit
 *   2. Pending Verification           (executive_officer) → verify (approve / reject / correction_required)
 *   3. Pending Asset Coding           (record_officer) → assign asset code
 *   4. Published                       (record_officer) → publish to public portal
 *
 * Critical business rules (spec §41):
 *   - Only approved (verified) properties receive asset codes (rule #7)
 *   - Only asset-coded properties can be published (rule #8)
 *   - Asset codes are immutable once assigned
 *   - All workflow transitions transactional + audited
 */
class PropertyController extends Controller
{
    public function __construct(
        private WorkflowEngine $workflows,
        private AuditLogger $audit,
        private AssetCodeGenerator $assetCodes,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Property::class);

        $properties = Property::query()
            ->visibleTo($request->user())
            ->with(['registeredBy', 'images'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('asset_code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), fn($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return PropertyResource::collection($properties);
    }

    public function store(StorePropertyRequest $request)
    {
        $this->authorize('create', Property::class);

        $property = DB::transaction(function () use ($request) {
            $user = $request->user();

            $property = Property::create(array_merge($request->validated(), [
                'status'                => PropertyStatus::Draft,
                'registered_by_user_id'=> $user->id,
                'generation_id'        => $user->teamMember?->team?->branch?->generation_id,
            ]));

            // Start the property_registration workflow (spec §10)
            $property->startWorkflow('property_registration', creatorId: $user->id);

            $this->audit->log(
                action: 'property.submit',
                entityType: Property::class,
                entityId: $property->id,
                category: 'workflow',
                newValues: ['name' => $property->name, 'price' => $property->price],
            );

            // Notify Executive Officers
            $this->notifyRole('executive_officer', 'New property awaiting verification', [
                'property_id' => $property->id,
                'name'        => $property->name,
                'city'        => $property->city,
            ]);

            return $property;
        });

        return new PropertyResource($property->load(['registeredBy', 'images']));
    }

    public function show(Request $request, Property $property)
    {
        $this->authorize('view', $property);
        $property->load(['registeredBy', 'images', 'documents', 'verifications', 'asset', 'publication']);
        return new PropertyResource($property);
    }

    public function update(Request $request, Property $property)
    {
        $this->authorize('update', $property);
        $property->update($request->validate([
            'name'        => ['sometimes', 'string', 'max:191'],
            'price'        => ['sometimes', 'numeric', 'min:0'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]));
        return new PropertyResource($property->fresh());
    }

    public function destroy(Request $request, Property $property)
    {
        $this->authorize('delete', $property);
        $property->delete();
        return response()->json(['message' => 'Property deleted.']);
    }

    /**
     * Step 2 — Executive Officer verifies the property (spec §11).
     */
    public function verify(Request $request, Property $property)
    {
        $this->authorize('verify', $property);
        $this->ensureStatus($property, [PropertyStatus::PendingVerification]);

        $data = $request->validate([
            'decision' => ['required', 'in:verify,reject,correction_required'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($property, $data, $request) {
            $property->verifications()->create([
                'verifier_user_id' => $request->user()->id,
                'decision'         => $data['decision'],
                'comment'          => $data['comment'] ?? null,
            ]);

            $next = match ($data['decision']) {
                'verify' => PropertyStatus::PendingAssetCoding,
                'reject' => PropertyStatus::Rejected,
                'correction_required' => PropertyStatus::CorrectionRequired,
            };

            $property->update(['status' => $next]);

            $action = match ($data['decision']) {
                'verify' => WorkflowAction::Verify,
                'reject' => WorkflowAction::Reject,
                'correction_required' => WorkflowAction::RequestCorrection,
            };

            $instance = $property->currentWorkflow();
            if ($instance) {
                if ($data['decision'] === 'reject') {
                    $this->workflows->reject($instance, $request->user(), $data['comment'] ?? 'Rejected by Executive Officer');
                } else {
                    $this->workflows->advance($instance, $request->user(), $action, $data['comment']);
                }
            }

            $this->audit->log(
                action: "property.verify.{$data['decision']}",
                entityType: Property::class,
                entityId: $property->id,
                category: 'workflow',
                newValues: ['decision' => $data['decision'], 'comment' => $data['comment'] ?? null],
            );

            if ($data['decision'] === 'verify') {
                $this->notifyRole('record_officer', 'Property awaiting asset coding', [
                    'property_id' => $property->id,
                    'name'        => $property->name,
                ]);
            }
        });

        return new PropertyResource($property->fresh());
    }

    /**
     * Step 3 — Record Officer assigns the immutable asset code (spec §12).
     *
     * Spec §41 rule #7 — only verified properties can receive an asset code.
     */
    public function assignAssetCode(Request $request, Property $property)
    {
        $this->authorize('assignAssetCode', $property);
        $this->ensureStatus($property, [PropertyStatus::PendingAssetCoding]);

        $assetCode = DB::transaction(function () use ($property, $request) {
            $code = $this->assetCodes->next(now()->year, $property->id, $request->user()->id);

            PropertyAsset::create([
                'property_id' => $property->id,
                'asset_code'  => $code,
                'assigned_by' => $request->user()->id,
            ]);

            $property->update([
                'asset_code' => $code,
                'status'     => PropertyStatus::Published, // ready for publication
            ]);

            $instance = $property->currentWorkflow();
            if ($instance) {
                $this->workflows->advance($instance, $request->user(), WorkflowAction::Assign, "Assigned asset code: {$code}");
            }

            $this->audit->log(
                action: 'property.assign_asset_code',
                entityType: Property::class,
                entityId: $property->id,
                category: 'identity',
                newValues: ['asset_code' => $code],
            );

            return $code;
        });

        return response()->json([
            'message'    => 'Asset code assigned. Property is now ready for publication.',
            'asset_code' => $assetCode,
            'property'   => new PropertyResource($property->fresh()),
        ]);
    }

    /**
     * Step 4 — Record Officer publishes the property (spec §12).
     *
     * Spec §41 rule #8 — only asset-coded properties can be published.
     */
    public function publish(Request $request, Property $property)
    {
        $this->authorize('publish', $property);

        // Critical rule (spec §41 rule #8)
        abort_if(!$property->asset_code, 422, 'Property must have an asset code before publication (spec §41 rule #8).');
        $this->ensureStatus($property, [PropertyStatus::Published, PropertyStatus::PendingAssetCoding]);

        DB::transaction(function () use ($property, $request) {
            PropertyPublication::create([
                'property_id'  => $property->id,
                'published_by' => $request->user()->id,
            ]);

            $property->update([
                'is_published' => true,
                'published_at' => now(),
                'status'       => PropertyStatus::Published,
            ]);

            $instance = $property->currentWorkflow();
            if ($instance) {
                $this->workflows->advance($instance, $request->user(), WorkflowAction::Publish, 'Published to public portal');
            }

            $this->audit->log(
                action: 'property.publish',
                entityType: Property::class,
                entityId: $property->id,
                category: 'workflow',
                newValues: ['published_at' => now()->toIso8601String()],
            );

            // Notify the Generation Leader who registered the property
            if ($property->registeredBy) {
                $property->registeredBy->notify(new \App\Notifications\WorkflowAdvanceNotification(
                    "Your property has been published: {$property->name}",
                    ['property_id' => $property->id, 'asset_code' => $property->asset_code],
                ));
            }
        });

        return new PropertyResource($property->fresh());
    }

    /**
     * Unpublish — admin-only safety valve (spec §31 — internal confidential info must not appear on portal).
     */
    public function unpublish(Request $request, Property $property)
    {
        $this->authorize('publish', $property);
        abort_if(!$property->is_published, 422, 'Property is not currently published.');

        DB::transaction(function () use ($property, $request) {
            $property->update([
                'is_published' => false,
                'status'       => PropertyStatus::Verified,
            ]);

            $publication = $property->publication;
            if ($publication) {
                $publication->update([
                    'unpublished_by' => $request->user()->id,
                    'unpublished_at' => now(),
                ]);
            }

            $this->audit->log(
                action: 'property.unpublish',
                entityType: Property::class,
                entityId: $property->id,
                category: 'workflow',
                severity: \App\Enums\AuditSeverity::Warning,
                newValues: ['unpublished_by' => $request->user()->official_id],
            );
        });

        return new PropertyResource($property->fresh());
    }

    // ─── Internals ───────────────────────────────────────────────────────

    private function ensureStatus(Property $property, array $allowed): void
    {
        if (!in_array($property->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Action requires status: " . implode(', ', array_map(fn($s) => $s->value, $allowed)) . ". Current: {$property->status->value}.",
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
}
