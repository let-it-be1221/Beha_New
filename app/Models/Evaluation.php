<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id', 'team_member_id', 'subject_user_id', 'evaluator_user_id',
        'total_score', 'weighted_score', 'decision',
        'evaluation_period_start', 'evaluation_period_end',
        'notes', 'finalized_at',
    ];

    protected $casts = [
        'total_score'   => 'decimal:2',
        'weighted_score'=> 'decimal:2',
        'finalized_at'  => 'datetime',
        'evaluation_period_start' => 'date',
        'evaluation_period_end'   => 'date',
    ];

    public function template(): BelongsTo { return $this->belongsTo(EvaluationTemplate::class); }
    public function teamMember(): BelongsTo { return $this->belongsTo(TeamMember::class); }
    public function subject(): BelongsTo { return $this->belongsTo(User::class, 'subject_user_id'); }
    public function evaluator(): BelongsTo { return $this->belongsTo(User::class, 'evaluator_user_id'); }
    public function scores(): HasMany { return $this->hasMany(EvaluationScore::class); }
}
