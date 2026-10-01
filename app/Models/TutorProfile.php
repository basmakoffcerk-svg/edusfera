<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorProfile extends Model
{
    /**
     * SECURITY (M1): is_verified, verification_status, rating_avg are mass-assignable
     * because they are managed by admin approval flows in Filament.
     */
    protected static function booted(): void
    {
        static::saved(function (TutorProfile $profile): void {
            if ($profile->wasChanged('avatar_path') && ! empty($profile->avatar_path)) {
                $user = $profile->user;
                if ($user && $user->avatar !== $profile->avatar_path) {
                    $user->updateQuietly([
                        'avatar' => $profile->avatar_path,
                    ]);
                }
            }
        });
    }

    protected $fillable = [
        'user_id',
        'subjects',
        'audiences',
        'price_per_hour',
        'experience_years',
        'legal_status',
        'unp',
        'payout_account',
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

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getAvatarUrl();
    }

    public function getAvatarUrl(): ?string
    {
        $path = $this->avatar_path ?: $this->user?->avatar;
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $clean = ltrim(str_replace('storage/', '', $path), '/');

        // Self-healing: if file exists in private storage, sync to public and public/storage
        $publicFile = storage_path('app/public/'.$clean);
        $privateFile = storage_path('app/private/'.$clean);
        $appFile = storage_path('app/'.$clean);
        $publicDirFile = public_path('storage/'.$clean);

        if (! file_exists($publicFile)) {
            @mkdir(dirname($publicFile), 0775, true);
            if (file_exists($privateFile) && is_file($privateFile)) {
                @copy($privateFile, $publicFile);
            } elseif (file_exists($appFile) && is_file($appFile)) {
                @copy($appFile, $publicFile);
            }
        }

        if (! is_link(public_path('storage')) && ! file_exists($publicDirFile)) {
            @mkdir(dirname($publicDirFile), 0775, true);
            $src = file_exists($publicFile) ? $publicFile : (file_exists($privateFile) ? $privateFile : $appFile);
            if (file_exists($src) && is_file($src)) {
                @copy($src, $publicDirFile);
            }
        }

        return asset('storage/'.$clean);
    }

    public function getDiplomaUrlAttribute(): ?string
    {
        return $this->getDiplomaUrl();
    }

    public function getDiplomaUrl(): ?string
    {
        $path = $this->diploma_path;
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $clean = ltrim(str_replace('storage/', '', $path), '/');

        // Self-healing: if file exists in private storage, sync to public and public/storage
        $publicFile = storage_path('app/public/'.$clean);
        $privateFile = storage_path('app/private/'.$clean);
        $appFile = storage_path('app/'.$clean);
        $publicDirFile = public_path('storage/'.$clean);

        if (! file_exists($publicFile)) {
            @mkdir(dirname($publicFile), 0775, true);
            if (file_exists($privateFile) && is_file($privateFile)) {
                @copy($privateFile, $publicFile);
            } elseif (file_exists($appFile) && is_file($appFile)) {
                @copy($appFile, $publicFile);
            }
        }

        if (! is_link(public_path('storage')) && ! file_exists($publicDirFile)) {
            @mkdir(dirname($publicDirFile), 0775, true);
            $src = file_exists($publicFile) ? $publicFile : (file_exists($privateFile) ? $privateFile : $appFile);
            if (file_exists($src) && is_file($src)) {
                @copy($src, $publicDirFile);
            }
        }

        return asset('storage/'.$clean);
    }
}
