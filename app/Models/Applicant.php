<?php

namespace App\Models;

use App\Traits\Workflowable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ApplicantStatus;

class Applicant extends Model
{
    use HasFactory, SoftDeletes, Workflowable;

    protected $fillable = [
        'application_code', 'full_name', 'email', 'phone', 'national_id',
        'address', 'education', 'experience', 'references', 'profile_photo_path',
        'status', 'reviewed_by_team_leader_id', 'assigned_branch_leader_id',
        'assigned_generation_id', 'assigned_branch_id', 'assigned_team_id',
        'record_officer_id', 'generated_official_id', 'generated_confidential_id',
        'approved_at', 'rejected_at', 'rejection_reason', 'terms_accepted_at',
    ];

    protected $casts = [
        'education'    => 'array',
        'experience'   => 'array',
        'references'   => 'array',
        'status'       => ApplicantStatus::class,
        'approved_at'  => 'datetime',
        'rejected_at'  => 'datetime',
        'terms_accepted_at' => 'datetime',
    ];

    public function reviewedByTeamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_team_leader_id');
    }

    public function assignedBranchLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_branch_leader_id');
    }

    public function recordOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'record_officer_id');
    }

    public function assignedGeneration(): BelongsTo
    {
        return $this->belongsTo(Generation::class, 'assigned_generation_id');
    }

    public function assignedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ApplicantReview::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ApplicantAssignment::class);
    }
}
