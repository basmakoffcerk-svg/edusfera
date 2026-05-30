<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomFile extends Model
{
    protected $fillable = [
        'classroom_session_id',
        'uploaded_by',
        'original_name',
        'path',
        'mime_type',
        'size_bytes',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassroomSession::class, 'classroom_session_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
