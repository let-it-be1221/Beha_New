<?php

namespace App\Policies;

use App\Models\User;

/**
 * UserPolicy — server-side authorization for users + confidential IDs.
 * Spec §6, §21.
 */
class UserPolicy
{
    public function view(User $viewer, User $target): bool
    {
        if ($viewer->id === $target->id) return true;
        if ($viewer->hasRole('system_administrator')) return true;
        // vertical scoping handled in query scope — this method is for direct view
        return $viewer->can('users.read');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('users.update') || $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('users.delete') && $user->id !== $target->id;
    }

    public function assignRole(User $user): bool
    {
        return $user->can('users.assign_role');
    }

    /**
     * Critical authorization for confidential ID decryption.
     * Spec §6 + §41 rule #3.
     */
    public function viewConfidential(User $viewer, User $target): bool
    {
        // Self cannot view own confidential ID (prevents leakage via self-service endpoints)
        if ($viewer->id === $target->id) return false;

        $allowed = config('beha.confidential_id_viewers', []);
        return $viewer->hasAnyRole($allowed);
    }
}
