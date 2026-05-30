<?php

declare(strict_types=1);

namespace App\Services\Lesson;

use App\Contracts\Lesson\LessonBooker;
use App\Domain\Lesson\BookingRequest;
use App\Domain\Lesson\CancelReason;
use App\Domain\Lesson\Dto\LessonDto;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\LessonCancelledNotification;
use App\Services\BookingService;
use App\Services\Payment\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Eloquent-backed implementation of the {@see LessonBooker} contract.
 *
 * Booking is delegated to {@see BookingService} (no business logic duplicated)
 * and refunds to {@see PaymentService}. Callers receive a {@see LessonDto} so
 * the Eloquent model never crosses the context boundary (Requirement 6.3).
 */
final class EloquentLessonBooker implements LessonBooker
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly PaymentService $paymentService,
    ) {}

    public function book(BookingRequest $req): LessonDto
    {
        $tutorProfile = TutorProfile::query()->findOrFail($req->tutorProfileId);
        $booker = User::query()->findOrFail($req->bookerUserId);

        $lesson = $req->isPackage()
            ? $this->bookingService->createPackageBooking(
                tutorProfile: $tutorProfile,
                booker: $booker,
                startTimesLocal: $req->startTimesLocal,
                notes: $req->notes,
                studentName: $req->studentName,
                studentPhone: $req->studentPhone,
                packageCode: $req->packageCode,
            )
            : $this->bookingService->createBooking(
                tutorProfile: $tutorProfile,
                booker: $booker,
                startTimeLocal: $req->firstSlot(),
                notes: $req->notes,
                studentName: $req->studentName,
                studentPhone: $req->studentPhone,
                packageCode: $req->packageCode,
            );

        return LessonDtoMapper::fromModel($lesson);
    }

    public function cancel(int $id, CancelReason $reason): void
    {
        $lesson = Lesson::query()->find($id);

        if ($lesson === null) {
            throw (new ModelNotFoundException)->setModel(Lesson::class, [$id]);
        }

        // Paid lessons are refunded (PaymentService handles the financial
        // movement and its own cancellation notifications).
        if ($lesson->payment_status === Lesson::PAYMENT_PAID) {
            $this->paymentService->refundLessonPayment($lesson, $reason->code);

            return;
        }

        // Unpaid lesson: just flip the status and notify participants — mirrors
        // the behaviour previously inlined in the Filament cancel action.
        $lesson->update(['status' => Lesson::STATUS_CANCELLED]);

        $fresh = $lesson->fresh();
        $fresh?->student?->notify(new LessonCancelledNotification($fresh));
        $fresh?->tutor?->notify(new LessonCancelledNotification($fresh));
    }
}
