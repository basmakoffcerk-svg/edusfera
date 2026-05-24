<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentGoal extends Model
{
    protected $fillable = [
        'student_id',
        'tutor_id',
        'subject',
        'exam_type',
        'current_score',
        'target_score',
        'exam_date',
        'status',
        'latest_diagnostic_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'current_score' => 'integer',
            'target_score' => 'integer',
            'exam_date' => 'date',
            'latest_diagnostic_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function examTracks(): HasMany
    {
        return $this->hasMany(ExamTrack::class);
    }

    public function diagnosticAttempts(): HasMany
    {
        return $this->hasMany(DiagnosticAttempt::class)->latest('taken_at');
    }

    public function skillGaps(): HasMany
    {
        return $this->hasMany(SkillGap::class);
    }

    public function homeworkAssignments(): HasMany
    {
        return $this->hasMany(HomeworkAssignment::class);
    }

    public function progressSnapshots(): HasMany
    {
        return $this->hasMany(ProgressSnapshot::class)->latest('snapshot_date');
    }
}
