<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDuplicate extends Model
{
    use HasFactory;

    protected $table = 'customer_duplicates';

    protected $fillable = [
        'new_customer_id', 'existing_customer_id', 'match_field', 'match_score', 'resolved', 'resolved_by', 'resolved_action',
    ];
}
