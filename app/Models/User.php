<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Services\MultiAccountService;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            if ($user->wasChanged('avatar') && ! empty($user->avatar)) {
                $tutorProfile = $user->tutorProfile;
                if ($tutorProfile && $tutorProfile->avatar_path !== $user->avatar) {
                    $tutorProfile->updateQuietly([
                        'avatar_path' => $user->avatar,
                    ]);
                }
            }
        });
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFilamentAvatarUrl();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatar = $this->avatar ?: $this->tutorProfile?->avatar_path;
        if (! $avatar) {
            return null;
        }

        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        $clean = ltrim(str_replace('storage/', '', $avatar), '/');

        // Self-healing: ensure file exists in public storage and public/storage directory
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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_verified',
        'google_id',
        'yandex_id',
        'avatar',
        'offer_accepted_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'offer_accepted_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isAdmin() || in_array($this->role, UserRole::allPanelRoles(), true);
        }

        if ($panel->getId() === 'site-admin') {
            return $this->isAdmin();
        }

        return false;
    }

    public function isAdmin(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role === UserRole::Admin;
        }

        return $this->role === 'admin' || $this->role === UserRole::Admin->value;
    }

    public function isTutor(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role === UserRole::Tutor;
        }

        return $this->role === 'tutor' || $this->role === UserRole::Tutor->value;
    }

    public function isStudent(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role === UserRole::Student;
        }

        return $this->role === 'student' || $this->role === UserRole::Student->value;
    }

    public function isParent(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role === UserRole::Parent;
        }

        return $this->role === 'parent' || $this->role === UserRole::Parent->value;
    }

    public function getRoleLabelAttribute(): string
    {
        return MultiAccountService::roleLabel($this->role);
    }

    public function tutorProfile(): HasOne
    {
        return $this->hasOne(TutorProfile::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'tutor_id');
    }

    public function tutorLessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'tutor_id');
    }

    public function studentLessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'student_id');
    }

    public function parentLessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'parent_id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(TutorAvailability::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function tutorBalance(): HasOne
    {
        return $this->hasOne(TutorBalance::class);
    }

    public function studentBalance(): HasOne
    {
        return $this->hasOne(StudentBalance::class);
    }

    public function studentBalanceLedgerEntries(): HasMany
    {
        return $this->hasMany(StudentBalanceLedgerEntry::class);
    }

    public function tutorConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'tutor_id');
    }

    public function studentConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'student_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function studentGoals(): HasMany
    {
        return $this->hasMany(StudentGoal::class, 'student_id');
    }

    public function coachedGoals(): HasMany
    {
        return $this->hasMany(StudentGoal::class, 'tutor_id');
    }

    public function examTracks(): HasMany
    {
        return $this->hasMany(ExamTrack::class, 'student_id');
    }

    public function coachedExamTracks(): HasMany
    {
        return $this->hasMany(ExamTrack::class, 'tutor_id');
    }

    public function diagnosticAttempts(): HasMany
    {
        return $this->hasMany(DiagnosticAttempt::class, 'student_id');
    }

    public function reviewedDiagnosticAttempts(): HasMany
    {
        return $this->hasMany(DiagnosticAttempt::class, 'tutor_id');
    }

    public function skillGaps(): HasMany
    {
        return $this->hasMany(SkillGap::class, 'student_id');
    }

    public function homeworkAssignments(): HasMany
    {
        return $this->hasMany(HomeworkAssignment::class, 'student_id');
    }

    public function assignedHomework(): HasMany
    {
        return $this->hasMany(HomeworkAssignment::class, 'tutor_id');
    }

    public function progressSnapshots(): HasMany
    {
        return $this->hasMany(ProgressSnapshot::class, 'student_id');
    }

    public function recordedProgressSnapshots(): HasMany
    {
        return $this->hasMany(ProgressSnapshot::class, 'tutor_id');
    }
}
