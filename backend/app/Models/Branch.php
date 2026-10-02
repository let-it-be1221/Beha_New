<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'generation_id', 'name', 'branch_number',
        'leader_user_id', 'description', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
