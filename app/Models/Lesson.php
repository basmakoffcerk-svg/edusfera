<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_PARTIALLY_REFUNDED = 'partially_refunded';

    protected $fillable = [
        'tutor_id',
        'student_id',
        'parent_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'price',
        'platform_commission',
        'net_amount',
        'status',
        'payment_status',
        'package_code',
        'package_lessons',
        'package_lessons_remaining',
        'package_parent_lesson_id',
        'package_total',
        'package_discount',
        'payment_lock_expires_at',
        'checkout_started_at',
        'meeting_link',
        'notes',
        'tutor_report_summary',
        'tutor_report_focus',
        'tutor_next_step',
        'tutor_homework_summary',
        'tutor_report_score',
        'tutor_reported_at',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'price' => 'decimal:2',
            'platform_commission' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'package_total' => 'decimal:2',
            'package_discount' => 'decimal:2',
            'payment_lock_expires_at' => 'datetime',
            'checkout_started_at' => 'datetime',
            'tutor_report_score' => 'integer',
            'tutor_reported_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $lesson): void {
            if (blank($lesson->meeting_link)) {
                $lesson->updateQuietly([
                    'meeting_link' => route('virtual.class', $lesson),
                ]);
            }
        });
    }

    public function getPackageLabelAttribute(): string
    {
        return match ($this->package_code) {
            'pack_4' => 'Траектория 4 занятия',
            'pack_8' => 'Траектория 8 занятий',
            default => 'Стартовое занятие',
        };
    }

    public function hasActivePaymentLock(): bool
    {
        return $this->payment_status === self::PAYMENT_UNPAID
            && $this->payment_lock_expires_at !== null
            && $this->payment_lock_expires_at->isFuture();
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function packageParent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'package_parent_lesson_id');
    }

    public function packageLessons(): HasMany
    {
        return $this->hasMany(self::class, 'package_parent_lesson_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(LessonReview::class);
    }

    public function homeworkAssignments(): HasMany
    {
        return $this->hasMany(HomeworkAssignment::class);
    }
}
