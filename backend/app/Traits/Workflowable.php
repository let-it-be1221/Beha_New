<?php

namespace App\Traits;

use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Services\Workflow\WorkflowEngine;

/**
 * Workflowable — attach to any model that participates in workflows
 * (Customer, Property, Applicant, Evaluation, ...).
 *
 * Provides:
 *   - workflowInstances() relation (polymorphic)
 *   - currentWorkflow() accessor
 *   - startWorkflow($type) helper
 */
trait Workflowable
{
    public function workflowInstances(): MorphMany
    {
        return $this->morphMany(WorkflowInstance::class, 'subject');
    }

    public function currentWorkflow(): ?WorkflowInstance
    {
        return $this->workflowInstances()
            ->whereNull('finalized_at')
            ->latest()
            ->first();
    }

    public function startWorkflow(string $type, ?int $creatorId = null): WorkflowInstance
    {
        return app(WorkflowEngine::class)->start(
            type: $type,
            subject: $this,
            creatorId: $creatorId ?? auth()->id(),
        );
    }
}
