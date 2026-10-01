<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function view(User $user, Property $p): bool
    {
        if ($p->is_published) return true; // public portal
        return $user->can('properties.read');
    }

    public function create(User $user): bool { return $user->can('properties.register'); }
    public function update(User $user, Property $p): bool { return $user->can('properties.update'); }
    public function delete(User $user, Property $p): bool { return $user->can('properties.delete'); }

    public function verify(User $user, Property $p): bool { return $user->can('properties.verify'); }
    public function approve(User $user, Property $p): bool { return $user->can('properties.approve'); }
    public function assignAssetCode(User $user, Property $p): bool { return $user->can('properties.assign_asset_code'); }
    public function publish(User $user, Property $p): bool { return $user->can('properties.publish'); }
    public function viewConfidential(User $user): bool { return $user->can('properties.view_confidential'); }
}
