<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillGap extends Model
{
    protected $fillable = [
        'student_goal_id',
        'diagnostic_attempt_id',
        'student_id',
        'subject',
        'topic',
        'severity',
        'status',
        'last_detected_at',
        'evidence',
    ];

    protected function casts(): array
    {
        return [
            'last_detected_at' => 'datetime',
            'evidence' => 'array',
        ];
    }

    public function studentGoal(): BelongsTo
    {
        return $this->belongsTo(StudentGoal::class);
    }

    public function diagnosticAttempt(): BelongsTo
    {
        return $this->belongsTo(DiagnosticAttempt::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
