<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        'offer_accepted_at',
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
        ];
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return in_array($this->role, ['admin', 'tutor', 'student', 'parent'], true);
        }

        if ($panel->getId() === 'site-admin') {
            $technicalEmail = mb_strtolower((string) config('site_admin.email', ''));

            return $this->role === 'admin'
                && $technicalEmail !== ''
                && mb_strtolower($this->email) === $technicalEmail;
        }

        return true;
    }

    public function tutorProfile(): HasOne
    {
        return $this->hasOne(TutorProfile::class);
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
