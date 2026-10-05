<?php

namespace App\Services\Workflow;

use App\Enums\WorkflowAction;
use App\Enums\WorkflowStatus;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Traits\Workflowable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * WorkflowEngine — the heart of Beha's reusable workflow system.
 *
 * Spec §23, §41.
 *
 * Responsibilities:
 *   1. start() — instantiate a workflow_instance + step 0
 *   2. advance() — move to the next step (with self-approval prevention)
 *   3. reject() — terminal state, audit + notify
 *   4. requestCorrection() — non-terminal revert for resubmission
 *
 * Every transition is wrapped in DB::transaction + audited.
 */
class WorkflowEngine
{
    public function __construct(
        private \App\Services\Audit\AuditLogger $audit,
    ) {}

    /**
     * Start a workflow of the given type for the given subject.
     *
     * @param int|null $creatorId  The user who initiated the workflow.
     *                              NULL for public-initiated workflows (e.g. applicant apply).
     */
    public function start(string $type, Model $subject, ?int $creatorId): WorkflowInstance
    {
        $definition = $this->definition($type);
        $firstStep  = $definition['steps'][1] ?? null;
        if (!$firstStep) {
            throw new \RuntimeException("Workflow [{$type}] has no steps defined.");
        }

        $ownerId = $this->resolveActorUserId($firstStep['actor'], $subject);

        return DB::transaction(function () use ($type, $subject, $creatorId, $firstStep, $ownerId) {
            $instance = WorkflowInstance::create([
                'workflow_type'          => $type,
                'subject_type'           => $subject::class,
                'subject_id'             => $subject->id,
                'current_step'           => $firstStep['name'],
                'current_owner_user_id'  => $ownerId,
                'created_by_user_id'      => $creatorId,
                'status'                 => WorkflowStatus::InProgress->value,
            ]);

            $instance->steps()->create([
                'step_index'     => 1,
                'step_name'      => $firstStep['name'],
                'actor_role'     => $firstStep['actor'],
                'expected_action'=> $firstStep['action'],
            ]);

            $this->audit->log(
                action: 'workflow.start',
                entityType: WorkflowInstance::class,
                entityId: $instance->id,
                category: 'workflow',
                newValues: [
                    'type'        => $type,
                    'subject'     => $subject::class . ':' . $subject->id,
                    'first_step'  => $firstStep['name'],
                ],
            );

            return $instance;
        });
    }

    /**
     * Advance the workflow one step. Throws on self-approval attempt.
     */
    public function advance(WorkflowInstance $instance, User $actor, WorkflowAction $action, ?string $comment = null): WorkflowInstance
    {
        if (config('workflows.prevent_self_approval', true)
            && (int) $instance->current_owner_user_id === $actor->id
            && $action !== WorkflowAction::Submit
        ) {
            throw new \App\Exceptions\WorkflowSelfApprovalException(
                'Users cannot approve their own submissions (spec §41 rule #11).',
            );
        }

        $definition = $this->definition($instance->workflow_type);
        $steps      = $definition['steps'];
        $currentKey = array_search($instance->current_step, array_column($steps, 'name'));

        if ($currentKey === false) {
            throw new \RuntimeException("Unknown step [{$instance->current_step}] for workflow [{$instance->workflow_type}].");
        }

        $currentStepIndex = $currentKey + 1;
        $nextStepIndex    = $currentStepIndex + 1;

        return DB::transaction(function () use ($instance, $actor, $action, $comment, $steps, $nextStepIndex) {
            $currentStep = $steps[$nextStepIndex - 1] ?? null;
            $nextStep     = $steps[$nextStepIndex] ?? null;

            $instance->actions()->create([
                'actor_user_id' => $actor->id,
                'action'        => $action->value,
                'from_step'     => $instance->current_step,
                'to_step'       => $nextStep ? $nextStep['name'] : null,
                'comment'       => $comment,
                'ip_address'    => Request::ip(),
                'user_agent'    => Request::userAgent(),
            ]);

            $instance->history()->create([
                'from_step'      => $instance->current_step,
                'to_step'        => $nextStep['name'] ?? null,
                'actor_user_id'  => $actor->id,
                'action'         => $action->value,
                'metadata'       => ['comment' => $comment],
            ]);

            if ($nextStep) {
                $instance->update([
                    'current_step'           => $nextStep['name'],
                    'current_owner_user_id'  => $this->resolveActorUserId($nextStep['actor'], $instance->subject),
                ]);
                $instance->steps()->create([
                    'step_index'     => $nextStepIndex,
                    'step_name'      => $nextStep['name'],
                    'actor_role'     => $nextStep['actor'],
                    'expected_action'=> $nextStep['action'],
                ]);
            } else {
                // Final step — finalize
                $instance->update([
                    'status'         => WorkflowStatus::Approved->value,
                    'finalized_at'  => now(),
                    'current_owner_user_id' => null,
                ]);
            }

            $this->audit->log(
                action: 'workflow.advance',
                entityType: WorkflowInstance::class,
                entityId: $instance->id,
                category: 'workflow',
                newValues: [
                    'action'    => $action->value,
                    'to_step'   => $nextStep['name'] ?? '(finalized)',
                    'actor'     => $actor->official_id,
                ],
            );

            return $instance->fresh();
        });
    }

    public function reject(WorkflowInstance $instance, User $actor, ?string $reason = null): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $actor, $reason) {
            $instance->actions()->create([
                'actor_user_id' => $actor->id,
                'action'        => WorkflowAction::Reject->value,
                'from_step'     => $instance->current_step,
                'to_step'       => null,
                'comment'       => $reason,
                'ip_address'    => Request::ip(),
                'user_agent'    => Request::userAgent(),
            ]);

            $instance->update([
                'status'         => WorkflowStatus::Rejected->value,
                'finalized_at'  => now(),
                'current_owner_user_id' => null,
            ]);

            $this->audit->log(
                action: 'workflow.reject',
                entityType: WorkflowInstance::class,
                entityId: $instance->id,
                category: 'workflow',
                severity: \App\Enums\AuditSeverity::Warning,
                newValues: ['reason' => $reason, 'rejected_by' => $actor->official_id],
            );

            return $instance->fresh();
        });
    }

    public function requestCorrection(WorkflowInstance $instance, User $actor, string $reason): WorkflowInstance
    {
        return DB::transaction(function () use ($instance, $actor, $reason) {
            $instance->actions()->create([
                'actor_user_id' => $actor->id,
                'action'        => WorkflowAction::RequestCorrection->value,
                'from_step'     => $instance->current_step,
                'to_step'       => 'draft',
                'comment'       => $reason,
                'ip_address'    => Request::ip(),
                'user_agent'    => Request::userAgent(),
            ]);

            $instance->update([
                'status'         => WorkflowStatus::CorrectionRequired->value,
                'current_step'   => 'correction_required',
            ]);

            $this->audit->log(
                action: 'workflow.correction_required',
                entityType: WorkflowInstance::class,
                entityId: $instance->id,
                category: 'workflow',
                newValues: ['reason' => $reason],
            );

            return $instance->fresh();
        });
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private function definition(string $type): array
    {
        $defs = config("workflows.definitions.{$type}");
        if (!$defs) {
            throw new \RuntimeException("Workflow definition not found: {$type}");
        }
        return $defs;
    }

    /**
     * Resolve the actor role string to a real user_id.
     * For 'team_member' and 'generation_leader', this resolves to the subject's owner.
     * For 'applicant', returns NULL (applicants aren't users — any Team Leader can pick up).
     * For system roles (team_leader, record_officer, etc.), returns NULL — resolved at runtime
     * when a user with that role takes action.
     */
    private function resolveActorUserId(string $actorRole, Model $subject): ?int
    {
        if ($actorRole === 'team_member') {
            if (method_exists($subject, 'sales_agent_user_id')) {
                return $subject->sales_agent_user_id ?? null;
            }
        }

        if ($actorRole === 'generation_leader') {
            if (method_exists($subject, 'registered_by_user_id')) {
                return $subject->registered_by_user_id ?? null;
            }
        }

        // 'applicant' role → NULL (applicants aren't in users table; any Team Leader can pick up)
        // System roles → NULL (resolved at runtime when a user takes action)
        return null;
    }
}
