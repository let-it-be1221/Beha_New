<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\Workflowable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\CustomerStatus;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization, Workflowable;

    protected $fillable = [
        'reference_code', 'full_name', 'email', 'phone', 'national_id',
        'date_of_birth', 'customer_source', 'sales_agent_user_id',
        'team_id', 'branch_id', 'generation_id', 'status', 'notes', 'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'metadata'      => 'array',
        'status'        => CustomerStatus::class,
    ];

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_agent_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CustomerReview::class);
    }

    public function referenceHistory(): HasMany
    {
        return $this->hasMany(CustomerReference::class);
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(CustomerDuplicate::class, 'new_customer_id');
    }

    public function scopeVisibleTo($q, User $user) { /* implemented in CustomerRepository */ }
}
