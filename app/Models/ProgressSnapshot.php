<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressSnapshot extends Model
{
    protected $fillable = [
        'student_goal_id',
        'exam_track_id',
        'student_id',
        'tutor_id',
        'snapshot_date',
        'current_score',
        'predicted_score',
        'target_score',
        'completed_topics_count',
        'active_skill_gaps_count',
        'summary',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'current_score' => 'integer',
            'predicted_score' => 'integer',
            'target_score' => 'integer',
            'completed_topics_count' => 'integer',
            'active_skill_gaps_count' => 'integer',
            'meta' => 'array',
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
}
