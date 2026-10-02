<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowInstance;

class WorkflowPolicy
{
    public function viewAny(User $user): bool { return $user->can('workflows.read'); }
    public function view(User $user, WorkflowInstance $w): bool
    {
        return $user->can('workflows.read');
    }

    public function advance(User $user, WorkflowInstance $w): bool
    {
        return $user->can('workflows.advance');
    }

    public function reject(User $user, WorkflowInstance $w): bool
    {
        return $user->can('workflows.reject');
    }
}
