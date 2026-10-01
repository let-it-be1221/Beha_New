<?php

namespace App\Policies;

use App\Models\Applicant;
use App\Models\User;

class ApplicantPolicy
{
    public function view(User $user, Applicant $a): bool
    {
        return $user->can('applicants.read')
            || ($user->id === $a->reviewed_by_team_leader_id)
            || ($user->id === $a->assigned_branch_leader_id);
    }

    public function create(User $user): bool { return true; } // public self-signup
    public function update(User $user, Applicant $a): bool { return $user->can('applicants.update'); }

    public function screen(User $user): bool { return $user->can('applicants.screen'); }
    public function assignBranch(User $user): bool { return $user->can('applicants.assign_branch'); }
    public function generateIds(User $user): bool { return $user->can('applicants.generate_ids'); }
    public function createAccount(User $user): bool { return $user->can('applicants.create_account'); }
    public function approve(User $user): bool { return $user->can('applicants.approve'); }
    public function reject(User $user): bool { return $user->can('applicants.reject'); }
}
