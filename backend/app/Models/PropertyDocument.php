<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyDocument extends Model
{
    use HasFactory;

    protected $table = 'property_documents';

    protected $fillable = [
        'property_id', 'document_type', 'file_path', 'file_name', 'mime_type', 'size_bytes', 'uploaded_by',
    ];
}
