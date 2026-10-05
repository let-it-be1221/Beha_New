<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelPromotion extends Model
{
    use HasFactory;

    protected $table = 'level_promotions';

    protected $fillable = [
        'team_member_id', 'evaluation_id', 'from_level', 'to_level', 'decision', 'approved_by', 'notes',
    ];

    const UPDATED_AT = null;
}
