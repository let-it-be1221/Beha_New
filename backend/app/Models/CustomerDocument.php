<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDocument extends Model
{
    use HasFactory;

    protected $table = 'customer_documents';

    protected $fillable = [
        'customer_id', 'document_type', 'file_path', 'file_name', 'mime_type', 'size_bytes', 'uploaded_by',
    ];
}
