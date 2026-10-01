<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Enums\WorkflowStatus;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $table = 'workflow_instances';

    protected $fillable = [
        'workflow_type', 'subject_type', 'subject_id', 'current_step',
        'current_owner_user_id', 'created_by_user_id', 'status', 'finalized_at',
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
        'status'       => WorkflowStatus::class,
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function currentOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_owner_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class, 'workflow_instance_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class, 'workflow_instance_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WorkflowComment::class, 'workflow_instance_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WorkflowAttachment::class, 'workflow_instance_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class, 'workflow_instance_id');
    }
}
