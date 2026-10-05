<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantAssignment extends Model
{
    use HasFactory;

    protected $table = 'applicant_assignments';

    protected $fillable = [
        'applicant_id', 'assigner_user_id', 'generation_id', 'branch_id', 'team_id',
    ];
}
