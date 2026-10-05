<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowAttachment extends Model
{
    use HasFactory;

    protected $table = 'workflow_attachments';

    protected $fillable = [
        'workflow_instance_id', 'uploaded_by', 'file_path', 'file_name', 'mime_type', 'size_bytes',
    ];
}
