<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Models\Lesson;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\LessonBookedStudentNotification;
use App\Notifications\LessonBookedTutorNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(private readonly PackageService $packageService) {}

    public function createBooking(
        TutorProfile $tutorProfile,
        User $booker,
        string $startTimeLocal,
        ?string $notes = null,
        ?string $studentName = null,
        ?string $studentPhone = null,
        string $packageCode = 'single',
    ): Lesson {
        $timezone = $this->displayTimezone();
        $duration = (int) config('booking.slot_duration_minutes', 60);
        $startLocal = CarbonImmutable::createFromFormat('Y-m-d H:i', $startTimeLocal, $timezone);

        if ($startLocal === false) {
            throw ValidationException::withMessages([
                'slot' => 'Не удалось распознать выбранное время.',
            ]);
        }

        $endLocal = $startLocal->addMinutes($duration);
        $this->ensureBookerCanBook($booker, $tutorProfile);
        $this->ensureSlotIsBookable($tutorProfile, $startLocal, $endLocal);

        if ($studentName !== null && ($booker->name === null || $booker->name === '')) {
            $booker->forceFill(['name' => $studentName])->save();
        }

        if ($studentPhone !== null && ($booker->phone === null || $booker->phone === '')) {
            $booker->forceFill(['phone' => $studentPhone])->save();
        }

        $lesson = DB::transaction(function () use ($booker, $duration, $endLocal, $notes, $packageCode, $startLocal, $tutorProfile): Lesson {
            User::query()->whereKey($tutorProfile->user_id)->lockForUpdate()->first();

            $startUtc = $startLocal->utc();
            $endUtc = $endLocal->utc();
            $nowUtc = now('UTC');

            $hasConflict = Lesson::query()
                ->where('tutor_id', $tutorProfile->user_id)
                ->where('status', '!=', Lesson::STATUS_CANCELLED)
                ->where(function ($query) use ($nowUtc) {
                    $query
                        ->where('payment_status', '!=', Lesson::PAYMENT_UNPAID)
                        ->orWhereNull('payment_lock_expires_at')
                        ->orWhere('payment_lock_expires_at', '>', $nowUtc);
                })
                ->where('start_time', '<', $endUtc)
                ->where('end_time', '>', $startUtc)
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                throw new SlotUnavailableException('Время занято.');
            }

            $price = (float) $tutorProfile->price_per_hour;
            $package = $this->packageService->resolve($packageCode, $price);
            $payableAmount = $package['total'];
            $commission = round($payableAmount * (float) config('payments.commission_rate', 0.15), 2);
            $netAmount = round($payableAmount - $commission, 2);

            $lesson = Lesson::query()->create([
                'tutor_id' => $tutorProfile->user_id,
                'student_id' => $booker->id,
                'parent_id' => $booker->role === 'parent' ? $booker->id : null,
                'start_time' => $startUtc,
                'end_time' => $endUtc,
                'duration_minutes' => $duration,
                'price' => number_format($price, 2, '.', ''),
                'platform_commission' => number_format($commission, 2, '.', ''),
                'net_amount' => number_format($netAmount, 2, '.', ''),
                'status' => Lesson::STATUS_PENDING,
                'payment_status' => Lesson::PAYMENT_UNPAID,
                'package_code' => $package['code'],
                'package_lessons' => $package['lessons'],
                'package_lessons_remaining' => $package['lessons'],
                'package_total' => number_format($package['total'], 2, '.', ''),
                'package_discount' => number_format($package['discount'], 2, '.', ''),
                'payment_lock_expires_at' => now('UTC')->addMinutes(15),
                'checkout_started_at' => now('UTC'),
                'notes' => $notes,
            ]);

            DB::afterCommit(function () use ($lesson): void {
                $lesson->loadMissing('student', 'tutor');
                $lesson->student?->notify(new LessonBookedStudentNotification($lesson));
                $lesson->tutor?->notify(new LessonBookedTutorNotification($lesson));
            });

            return $lesson;
        });

        return $lesson->load('student', 'tutor', 'parent');
    }

    /**
     * @param  array<int, string>  $startTimesLocal
     */
    public function createPackageBooking(
        TutorProfile $tutorProfile,
        User $booker,
        array $startTimesLocal,
        ?string $notes = null,
        ?string $studentName = null,
        ?string $studentPhone = null,
        string $packageCode = 'single',
    ): Lesson {
        $timezone = $this->displayTimezone();
        $duration = (int) config('booking.slot_duration_minutes', 60);
        $price = (float) $tutorProfile->price_per_hour;
        $package = $this->packageService->resolve($packageCode, $price);

        if ($package['lessons'] <= 1) {
            return $this->createBooking(
                tutorProfile: $tutorProfile,
                booker: $booker,
                startTimeLocal: (string) ($startTimesLocal[0] ?? ''),
                notes: $notes,
                studentName: $studentName,
                studentPhone: $studentPhone,
                packageCode: $packageCode,
            );
        }

        $starts = collect($startTimesLocal)
            ->map(fn (string $startTimeLocal): string => trim($startTimeLocal))
            ->filter()
            ->unique()
            ->map(function (string $startTimeLocal) use ($timezone): CarbonImmutable {
                $startLocal = CarbonImmutable::createFromFormat('Y-m-d H:i', $startTimeLocal, $timezone);

                if ($startLocal === false) {
                    throw ValidationException::withMessages([
                        'slots' => 'Не удалось распознать одно из выбранных времен.',
                    ]);
                }

                return $startLocal;
            })
            ->sortBy(fn (CarbonImmutable $startLocal): int => $startLocal->getTimestamp())
            ->values();

        if ($starts->count() !== $package['lessons']) {
            throw ValidationException::withMessages([
                'slots' => "Выберите ровно {$package['lessons']} слотов для этого пакета.",
            ]);
        }

        $this->ensureBookerCanBook($booker, $tutorProfile);

        $previousEnd = null;

        foreach ($starts as $startLocal) {
            $endLocal = $startLocal->addMinutes($duration);
            $this->ensureSlotIsBookable($tutorProfile, $startLocal, $endLocal);

            if ($previousEnd instanceof CarbonImmutable && $startLocal->lt($previousEnd)) {
                throw ValidationException::withMessages([
                    'slots' => 'Выбранные слоты пересекаются между собой.',
                ]);
            }

            $previousEnd = $endLocal;
        }

        if ($studentName !== null && ($booker->name === null || $booker->name === '')) {
            $booker->forceFill(['name' => $studentName])->save();
        }

        if ($studentPhone !== null && ($booker->phone === null || $booker->phone === '')) {
            $booker->forceFill(['phone' => $studentPhone])->save();
        }

        $parentLesson = DB::transaction(function () use ($booker, $duration, $notes, $package, $price, $starts, $tutorProfile): Lesson {
            User::query()->whereKey($tutorProfile->user_id)->lockForUpdate()->first();

            foreach ($starts as $startLocal) {
                $startUtc = $startLocal->utc();
                $endUtc = $startLocal->addMinutes($duration)->utc();
                $nowUtc = now('UTC');

                $hasConflict = Lesson::query()
                    ->where('tutor_id', $tutorProfile->user_id)
                    ->where('status', '!=', Lesson::STATUS_CANCELLED)
                    ->where(function ($query) use ($nowUtc) {
                        $query
                            ->where('payment_status', '!=', Lesson::PAYMENT_UNPAID)
                            ->orWhereNull('payment_lock_expires_at')
                            ->orWhere('payment_lock_expires_at', '>', $nowUtc);
                    })
                    ->where('start_time', '<', $endUtc)
                    ->where('end_time', '>', $startUtc)
                    ->lockForUpdate()
                    ->exists();

                if ($hasConflict) {
                    throw new SlotUnavailableException('Один из выбранных слотов уже занят.');
                }
            }

            $payableAmount = $package['total'];
            $commission = round($payableAmount * (float) config('payments.commission_rate', 0.15), 2);
            $netAmount = round($payableAmount - $commission, 2);
            $paymentLockExpiresAt = now('UTC')->addMinutes(15);
            $checkoutStartedAt = now('UTC');
            $firstStart = $starts->first();

            $parentLesson = Lesson::query()->create([
                'tutor_id' => $tutorProfile->user_id,
                'student_id' => $booker->id,
                'parent_id' => $booker->role === 'parent' ? $booker->id : null,
                'start_time' => $firstStart->utc(),
                'end_time' => $firstStart->addMinutes($duration)->utc(),
                'duration_minutes' => $duration,
                'price' => number_format($price, 2, '.', ''),
                'platform_commission' => number_format($commission, 2, '.', ''),
                'net_amount' => number_format($netAmount, 2, '.', ''),
                'status' => Lesson::STATUS_PENDING,
                'payment_status' => Lesson::PAYMENT_UNPAID,
                'package_code' => $package['code'],
                'package_lessons' => $package['lessons'],
                'package_lessons_remaining' => $package['lessons'],
                'package_total' => number_format($package['total'], 2, '.', ''),
                'package_discount' => number_format($package['discount'], 2, '.', ''),
                'payment_lock_expires_at' => $paymentLockExpiresAt,
                'checkout_started_at' => $checkoutStartedAt,
                'notes' => $notes,
            ]);

            $starts->skip(1)->each(function (CarbonImmutable $startLocal) use ($booker, $duration, $package, $parentLesson, $paymentLockExpiresAt, $price, $tutorProfile): void {
                Lesson::query()->create([
                    'tutor_id' => $tutorProfile->user_id,
                    'student_id' => $booker->id,
                    'parent_id' => $booker->role === 'parent' ? $booker->id : null,
                    'start_time' => $startLocal->utc(),
                    'end_time' => $startLocal->addMinutes($duration)->utc(),
                    'duration_minutes' => $duration,
                    'price' => number_format($price, 2, '.', ''),
                    'platform_commission' => '0.00',
                    'net_amount' => '0.00',
                    'status' => Lesson::STATUS_PENDING,
                    'payment_status' => Lesson::PAYMENT_UNPAID,
                    'package_code' => $package['code'],
                    'package_lessons' => 1,
                    'package_lessons_remaining' => null,
                    'package_parent_lesson_id' => $parentLesson->id,
                    'package_total' => null,
                    'package_discount' => '0.00',
                    'payment_lock_expires_at' => $paymentLockExpiresAt,
                    'checkout_started_at' => $parentLesson->checkout_started_at,
                ]);
            });

            DB::afterCommit(function () use ($parentLesson): void {
                $parentLesson->loadMissing('student', 'tutor');
                $parentLesson->student?->notify(new LessonBookedStudentNotification($parentLesson));
                $parentLesson->tutor?->notify(new LessonBookedTutorNotification($parentLesson));
            });

            return $parentLesson;
        });

        return $parentLesson->load('student', 'tutor', 'parent', 'packageLessons');
    }

    public function getAvailableSlots(TutorProfile $tutorProfile, CarbonImmutable $dateLocal): array
    {
        $timezone = $this->displayTimezone();
        $duration = (int) config('booking.slot_duration_minutes', 60);
        $dayStartLocal = $dateLocal->startOfDay();
        $dayEndLocal = $dayStartLocal->endOfDay();
        $previousDay = $dayStartLocal->subDay();

        $availability = TutorAvailability::query()
            ->where('user_id', $tutorProfile->user_id)
            ->whereIn('day_of_week', [$dayStartLocal->dayOfWeek, $previousDay->dayOfWeek])
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($availability->isEmpty()) {
            return [];
        }

        $busyLessons = Lesson::query()
            ->where('tutor_id', $tutorProfile->user_id)
            ->where('status', '!=', Lesson::STATUS_CANCELLED)
            ->where(function ($query) {
                $query
                    ->where('payment_status', '!=', Lesson::PAYMENT_UNPAID)
                    ->orWhereNull('payment_lock_expires_at')
                    ->orWhere('payment_lock_expires_at', '>', now('UTC'));
            })
            ->where('start_time', '<', $dayEndLocal->utc())
            ->where('end_time', '>', $dayStartLocal->utc())
            ->get(['start_time', 'end_time']);

        $minimumAllowed = CarbonImmutable::now($timezone)->addHours((int) config('booking.minimum_notice_hours', 2));

        $slots = [];

        foreach ($availability as $window) {
            $anchorDate = (int) $window->day_of_week === $dayStartLocal->dayOfWeek
                ? $dayStartLocal
                : $previousDay;
            [$cursor, $windowEnd] = $this->windowBounds($anchorDate, (string) $window->start_time, (string) $window->end_time);

            if (! $cursor || ! $windowEnd) {
                continue;
            }

            while ($cursor->addMinutes($duration) <= $windowEnd) {
                $slotEnd = $cursor->addMinutes($duration);
                $slotStartUtc = $cursor->utc();
                $slotEndUtc = $slotEnd->utc();

                $isBusy = $busyLessons->contains(function (Lesson $lesson) use ($slotEndUtc, $slotStartUtc): bool {
                    return $lesson->start_time->lt($slotEndUtc) && $lesson->end_time->gt($slotStartUtc);
                });

                if (
                    ! $isBusy
                    && $cursor->greaterThan($minimumAllowed)
                    && $cursor->betweenIncluded($dayStartLocal, $dayEndLocal)
                ) {
                    $slots[] = [
                        'value' => $cursor->format('Y-m-d H:i'),
                        'label' => $cursor->format('H:i'),
                        'start' => $cursor,
                        'end' => $slotEnd,
                    ];
                }

                $cursor = $cursor->addMinutes($duration);
            }
        }

        return $slots;
    }

    public function minBookableDate(): CarbonImmutable
    {
        return CarbonImmutable::now($this->displayTimezone())->addDay()->startOfDay();
    }

    public function displayTimezone(): string
    {
        return (string) config('booking.display_timezone', 'Europe/Minsk');
    }

    private function ensureBookerCanBook(User $booker, TutorProfile $tutorProfile): void
    {
        if (! in_array($booker->role, ['student', 'parent'], true)) {
            throw ValidationException::withMessages([
                'slot' => 'Запись доступна только ученикам и родителям.',
            ]);
        }

        if ($booker->id === $tutorProfile->user_id) {
            throw ValidationException::withMessages([
                'slot' => 'Нельзя записаться к самому себе.',
            ]);
        }

        if (! $tutorProfile->is_verified) {
            throw ValidationException::withMessages([
                'slot' => 'Профиль репетитора пока недоступен для бронирования.',
            ]);
        }

        if ((float) $tutorProfile->price_per_hour <= 0) {
            throw ValidationException::withMessages([
                'slot' => 'У этого репетитора пока не настроена стоимость занятия.',
            ]);
        }
    }

    private function ensureSlotIsBookable(TutorProfile $tutorProfile, CarbonImmutable $startLocal, CarbonImmutable $endLocal): void
    {
        $timezone = $this->displayTimezone();
        $nowLocal = CarbonImmutable::now($timezone);

        if ($startLocal->lessThanOrEqualTo($nowLocal)) {
            throw ValidationException::withMessages([
                'slot' => 'Нельзя записаться на прошедшее время.',
            ]);
        }

        if ($startLocal->lessThanOrEqualTo($nowLocal->addHours((int) config('booking.minimum_notice_hours', 2)))) {
            throw ValidationException::withMessages([
                'slot' => 'Запись возможна минимум за 2 часа до начала урока.',
            ]);
        }

        $availability = TutorAvailability::query()
            ->where('user_id', $tutorProfile->user_id)
            ->whereIn('day_of_week', [$startLocal->dayOfWeek, $startLocal->subDay()->dayOfWeek])
            ->where('is_active', true)
            ->get();

        $isWithinAvailability = $availability->contains(function (TutorAvailability $window) use ($endLocal, $startLocal): bool {
            $anchorDate = (int) $window->day_of_week === $startLocal->dayOfWeek
                ? $startLocal->startOfDay()
                : $startLocal->subDay()->startOfDay();
            [$windowStart, $windowEnd] = $this->windowBounds($anchorDate, (string) $window->start_time, (string) $window->end_time);

            if (! $windowStart || ! $windowEnd) {
                return false;
            }

            return $startLocal->greaterThanOrEqualTo($windowStart) && $endLocal->lessThanOrEqualTo($windowEnd);
        });

        if (! $isWithinAvailability) {
            throw ValidationException::withMessages([
                'slot' => 'Выбранное время вне графика репетитора.',
            ]);
        }
    }

    /**
     * @return array{0: CarbonImmutable|false, 1: CarbonImmutable|false}
     */
    private function windowBounds(CarbonImmutable $dayStart, string $startTime, string $endTime): array
    {
        $timezone = $this->displayTimezone();
        $windowStart = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dayStart->format('Y-m-d').' '.$startTime, $timezone);
        $windowEnd = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $dayStart->format('Y-m-d').' '.$endTime, $timezone);

        if ($windowStart === false || $windowEnd === false) {
            return [false, false];
        }

        if ($windowEnd->lessThanOrEqualTo($windowStart)) {
            $windowEnd = $windowEnd->addDay();
        }

        return [$windowStart, $windowEnd];
    }
}
