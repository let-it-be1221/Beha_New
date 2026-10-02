<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationCriterion extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id', 'code', 'label', 'weight', 'min_score', 'max_score',
        'passing_score', 'required_evidence', 'sort_order',
    ];

    protected $casts = [
        'weight'         => 'decimal:2',
        'min_score'      => 'decimal:2',
        'max_score'      => 'decimal:2',
        'passing_score'  => 'decimal:2',
        'sort_order'    => 'integer',
        'required_evidence' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EvaluationTemplate::class);
    }
}
