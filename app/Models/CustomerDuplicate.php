<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDuplicate extends Model
{
    use HasFactory;

    protected $table = 'customer_duplicates';

    protected $fillable = [];
}
