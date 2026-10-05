<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdGenerationHistory extends Model
{
    use HasFactory;

    protected $table = 'id_generation_history';

    protected $fillable = [
        'sequence_key', 'generated_value', 'subject_type', 'subject_id', 'actor_user_id', 'ip_address', 'user_agent', 'payload',
    ];

    const UPDATED_AT = null;
}
