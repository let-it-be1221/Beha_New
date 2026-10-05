<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyPublication extends Model
{
    use HasFactory;

    protected $table = 'property_publications';

    protected $fillable = [
        'property_id', 'published_by', 'published_at', 'unpublished_by', 'unpublished_at',
    ];
}
