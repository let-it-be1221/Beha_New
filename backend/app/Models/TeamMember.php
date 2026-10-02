<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'team_members';

    protected $fillable = [
        'user_id', 'team_id', 'level', 'joined_at', 'promoted_at', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'level'      => 'integer',
        'joined_at'  => 'date',
        'promoted_at'=> 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(LevelPromotion::class);
    }

    public function ranking()
    {
        return $this->hasOne(PerformanceRanking::class);
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
