<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamTrack extends Model
{
    protected $fillable = [
        'student_goal_id',
        'student_id',
        'tutor_id',
        'title',
        'format',
        'status',
        'weekly_sessions_target',
        'starts_on',
        'ends_on',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'weekly_sessions_target' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'meta' => 'array',
        ];
    }

    public function studentGoal(): BelongsTo
    {
        return $this->belongsTo(StudentGoal::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function diagnosticAttempts(): HasMany
    {
        return $this->hasMany(DiagnosticAttempt::class)->latest('taken_at');
    }

    public function progressSnapshots(): HasMany
    {
        return $this->hasMany(ProgressSnapshot::class)->latest('snapshot_date');
    }
}
