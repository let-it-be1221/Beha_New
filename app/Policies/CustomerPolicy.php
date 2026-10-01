<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function view(User $user, Customer $customer): bool
    {
        if ($user->can('customers.read')) {
            return true;
        }
        return $user->id === $customer->sales_agent_user_id
            || ($user->team_id && $user->team_id === $customer->team_id);
    }

    public function create(User $user): bool { return $user->can('customers.register'); }
    public function update(User $user, Customer $c): bool { return $user->can('customers.update'); }
    public function delete(User $user, Customer $c): bool { return $user->can('customers.delete'); }

    public function evaluate(User $user, Customer $c): bool { return $user->can('customers.evaluate'); }
    public function verify(User $user, Customer $c): bool { return $user->can('customers.verify'); }
    public function approve(User $user, Customer $c): bool { return $user->can('customers.approve'); }
    public function export(User $user): bool { return $user->can('customers.export'); }
    public function viewConfidential(User $user): bool { return $user->can('customers.view_confidential'); }
}
