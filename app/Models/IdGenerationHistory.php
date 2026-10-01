<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdGenerationHistory extends Model
{
    use HasFactory;

    protected $table = 'id_generation_history';

    public $timestamps = false; // only created_at — see migration

    protected $fillable = [
        'sequence_key', 'generated_value', 'subject_type', 'subject_id',
        'actor_user_id', 'ip_address', 'user_agent', 'payload', 'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];
}
