<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'subjects',
        'audiences',
        'price_per_hour',
        'experience_years',
        'legal_status',
        'bio',
        'education_summary',
        'teaching_methodology',
        'achievements',
        'homework_policy',
        'lesson_formats',
        'lesson_languages',
        'exam_specializations',
        'average_score_growth',
        'students_prepared_count',
        'max_recent_score',
        'diagnostic_supported',
        'intro_video_url',
        'trial_lesson_minutes',
        'avatar_path',
        'telegram_username',
        'diploma_path',
        'is_verified',
        'verification_status',
        'verification_submitted_at',
        'onboarding_completed_at',
        'rating_avg',
        'contact_bypass_attempts',
        'search_penalized_until',
    ];

    protected function casts(): array
    {
        return [
            'subjects' => 'array',
            'audiences' => 'array',
            'lesson_formats' => 'array',
            'lesson_languages' => 'array',
            'exam_specializations' => 'array',
            'price_per_hour' => 'decimal:2',
            'average_score_growth' => 'integer',
            'students_prepared_count' => 'integer',
            'max_recent_score' => 'integer',
            'diagnostic_supported' => 'boolean',
            'trial_lesson_minutes' => 'integer',
            'is_verified' => 'boolean',
            'verification_submitted_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'rating_avg' => 'decimal:2',
            'contact_bypass_attempts' => 'integer',
            'search_penalized_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentGoals(): HasMany
    {
        return $this->hasMany(StudentGoal::class, 'tutor_id', 'user_id');
    }

    public function examTracks(): HasMany
    {
        return $this->hasMany(ExamTrack::class, 'tutor_id', 'user_id');
    }
}
