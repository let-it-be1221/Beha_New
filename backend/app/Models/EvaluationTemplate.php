<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'target_level', 'passing_score', 'is_active', 'metadata',
    ];

    protected $casts = [
        'passing_score'  => 'decimal:2',
        'is_active'      => 'boolean',
        'target_level'   => 'integer',
        'metadata'       => 'array',
    ];

    public function criteria(): HasMany
    {
        return $this->hasMany(EvaluationCriterion::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
