<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\ID\AssetCodeGenerator;
use App\Services\ID\ConfidentialIdGenerator;
use App\Services\ID\CustomerReferenceGenerator;
use App\Services\ID\IdHistoryRecorder;
use App\Services\ID\IdSequenceService;
use App\Services\ID\OfficialIdGenerator;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider — binds domain services + global boot logic.
 */
class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        // Interface → implementation bindings can be added here as the codebase grows.
    ];

    public function register(): void
    {
        // ID generation services — singletons, internal state is per-call.
        $this->app->singleton(IdSequenceService::class);
        $this->app->singleton(IdHistoryRecorder::class);
        $this->app->singleton(OfficialIdGenerator::class);
        $this->app->singleton(ConfidentialIdGenerator::class);
        $this->app->singleton(CustomerReferenceGenerator::class);
        $this->app->singleton(AssetCodeGenerator::class);

        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(WorkflowEngine::class);
    }

    public function boot(): void
    {
        // Model scoping helpers (spec §33 — global search across authorized info)
        \App\Models\Customer::macro('visibleTo', function ($query, User $user) {
            if ($user->hasRole('system_administrator')) return $query;
            if ($user->isGenerationLeader()) {
                return $query->where('generation_id', $user->teamMember?->team?->branch?->generation_id);
            }
            if ($user->isBranchLeader()) {
                return $query->where('branch_id', $user->teamMember?->team?->branch_id);
            }
            if ($user->isTeamLeader()) {
                return $query->where('team_id', $user->teamMember?->team_id);
            }
            return $query->where('sales_agent_user_id', $user->id);
        });

        \App\Models\Property::macro('visibleTo', function ($query, User $user) {
            if ($user->hasRole('system_administrator')) return $query;
            // Properties are visible to all internal users (public portal gating handled separately)
            return $query;
        });

        \App\Models\Applicant::macro('visibleTo', function ($query, User $user) {
            if ($user->hasRole('system_administrator')) return $query;
            if ($user->hasRole('team_leader')) {
                return $query->where('reviewed_by_team_leader_id', $user->id)
                    ->orWhereNull('reviewed_by_team_leader_id');
            }
            if ($user->hasRole('branch_leader')) {
                return $query->where('assigned_branch_leader_id', $user->id)
                    ->orWhereNotNull('reviewed_by_team_leader_id');
            }
            return $query->whereRaw('1=0');
        });

        \App\Models\WorkflowInstance::macro('visibleTo', function ($query, User $user) {
            if ($user->hasRole('system_administrator')) return $query;
            return $query->where('current_owner_user_id', $user->id)
                ->orWhere('created_by_user_id', $user->id);
        });
    }
}
