<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowAction extends Model
{
    use HasFactory;

    protected $table = 'workflow_actions';

    protected $fillable = [
        'workflow_instance_id', 'actor_user_id', 'action', 'from_step', 'to_step', 'comment', 'ip_address', 'user_agent',
    ];
}
