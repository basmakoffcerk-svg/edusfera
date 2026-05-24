<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonReview extends Model
{
    protected $fillable = [
        'lesson_id',
        'reviewer_id',
        'rating',
        'feedback',
        'is_public',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_public' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
