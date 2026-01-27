<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'uploaded_by',
        'original_name',
        'stored_name',
        'file_type',
        'file_size',
        'file_path',
        'version',
        'is_latest',
        'document_category',
        'remarks',
    ];

    /**
     * The project this document belongs to
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The user who uploaded the document
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}