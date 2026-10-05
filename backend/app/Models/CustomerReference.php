<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReference extends Model
{
    use HasFactory;

    protected $table = 'customer_references';

    protected $fillable = [
        'customer_id', 'reference_code', 'issued_by', 'note',
    ];

    public $timestamps = false;
}
