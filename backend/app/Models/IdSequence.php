<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdSequence extends Model
{
    use HasFactory;

    protected $table = 'id_sequences';

    protected $fillable = [
        'sequence_key', 'next_value', 'prefix', 'padding', 'last_used_at',
    ];

    protected $casts = [
        'next_value'    => 'integer',
        'padding'       => 'integer',
        'last_used_at'  => 'datetime',
    ];
}
