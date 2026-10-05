<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceRecord extends Model
{
    use HasFactory;

    protected $table = 'performance_records';

    protected $fillable = [
        'team_member_id', 'period_start', 'period_end', 'metric', 'value',
    ];

    public $timestamps = false;
}
