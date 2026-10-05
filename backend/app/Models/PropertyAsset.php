<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyAsset extends Model
{
    use HasFactory;

    protected $table = 'property_assets';

    protected $fillable = [
        'property_id', 'asset_code', 'assigned_by',
    ];

    public $timestamps = false;
}
