<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceRankingHistory extends Model
{
    use HasFactory;

    protected $table = 'performance_ranking_history';

    protected $fillable = [
        'team_member_id', 'rank_in_team', 'rank_in_branch', 'rank_in_generation', 'overall_score', 'reason',
    ];
}
