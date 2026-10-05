<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $table = 'workflow_steps';

    protected $fillable = [
        'workflow_instance_id', 'step_index', 'step_name', 'actor_role', 'expected_action', 'completed_at', 'completed_by',
    ];

    public $timestamps = false;
}
