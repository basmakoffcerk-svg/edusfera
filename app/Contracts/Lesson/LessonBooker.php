<?php

declare(strict_types=1);

namespace App\Contracts\Lesson;

use App\Domain\Lesson\BookingRequest;
use App\Domain\Lesson\CancelReason;
use App\Domain\Lesson\Dto\LessonDto;

/**
 * Write-side contract for the Lesson bounded context.
 *
 * Mutations to the Lesson aggregate (booking, cancellation) go through this
 * interface, so UI and API layers never mutate the Eloquent model directly
 * (Requirement 6.3).
 */
interface LessonBooker
{
    /**
     * Book a lesson (or package of lessons) and return the created aggregate.
     */
    public function book(BookingRequest $req): LessonDto;

    /**
     * Cancel the lesson, issuing a refund when it was already paid.
     */
    public function cancel(int $id, CancelReason $reason): void;
}
