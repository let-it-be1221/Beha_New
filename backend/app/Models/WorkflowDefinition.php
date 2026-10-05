<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowDefinition extends Model
{
    use HasFactory;

    protected $table = 'workflow_definitions';

    protected $fillable = [
        'code', 'label', 'config', 'is_active',
    ];
}
