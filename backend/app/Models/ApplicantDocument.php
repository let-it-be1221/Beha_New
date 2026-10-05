<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantDocument extends Model
{
    use HasFactory;

    protected $table = 'applicant_documents';

    protected $fillable = [
        'applicant_id', 'document_type', 'file_path', 'file_name', 'mime_type', 'size_bytes', 'uploaded_by',
    ];
}
