<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantReview extends Model
{
    use HasFactory;

    protected $table = 'applicant_reviews';

    protected $fillable = [
        'applicant_id', 'reviewer_user_id', 'review_stage', 'decision', 'comment',
    ];
}
