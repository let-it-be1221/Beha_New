<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\Generation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToOrganization — for entities scoped to a generation / branch / team
 * (Customer, Property, TeamMember, ...).
 */
trait BelongsToOrganization
{
    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
