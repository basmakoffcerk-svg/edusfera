<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkAssignment extends Model
{
    protected $fillable = [
        'student_goal_id',
        'lesson_id',
        'student_id',
        'tutor_id',
        'title',
        'instructions',
        'source',
        'status',
        'assigned_at',
        'due_at',
        'completed_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function studentGoal(): BelongsTo
    {
        return $this->belongsTo(StudentGoal::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
