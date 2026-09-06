<?php

declare(strict_types=1);

namespace App\Domain\Lesson;

/**
 * Readonly value object describing why a lesson is being cancelled.
 *
 * The {@see CancelReason::$code} maps onto the reason string already understood
 * by App\Services\Payment\PaymentService::refundLessonPayment (e.g.
 * "lesson_cancelled"), keeping the contract aligned with the real call sites
 * (Requirement 6.3).
 */
final readonly class CancelReason
{
    public const LESSON_CANCELLED = 'lesson_cancelled';

    public const TUTOR_CANCELLED = 'tutor_cancelled';

    public const STUDENT_CANCELLED = 'student_cancelled';

    public const ADMIN_CANCELLED = 'admin_cancelled';

    public function __construct(
        public string $code = self::LESSON_CANCELLED,
        public ?string $comment = null,
    ) {}

    public static function lessonCancelled(?string $comment = null): self
    {
        return new self(self::LESSON_CANCELLED, $comment);
    }
}
