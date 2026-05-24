<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticAttempt extends Model
{
    protected $fillable = [
        'student_goal_id',
        'exam_track_id',
        'student_id',
        'tutor_id',
        'subject',
        'exam_type',
        'source',
        'score',
        'max_score',
        'duration_minutes',
        'taken_at',
        'breakdown',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'max_score' => 'integer',
            'duration_minutes' => 'integer',
            'taken_at' => 'datetime',
            'breakdown' => 'array',
        ];
    }

    public function studentGoal(): BelongsTo
    {
        return $this->belongsTo(StudentGoal::class);
    }

    public function examTrack(): BelongsTo
    {
        return $this->belongsTo(ExamTrack::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function skillGaps(): HasMany
    {
        return $this->hasMany(SkillGap::class);
    }
}
