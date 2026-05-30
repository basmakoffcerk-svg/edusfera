<?php

declare(strict_types=1);

namespace App\Domain\Lesson;

/**
 * Readonly value object describing a request to book a lesson (or a package).
 *
 * Mirrors the parameters accepted by App\Services\BookingService::createBooking
 * and createPackageBooking, but as a transport-only structure free of Eloquent
 * so callers (Filament, API) never touch the persistence layer directly
 * (Requirement 6.2, 6.3).
 */
final readonly class BookingRequest
{
    /**
     * @param  list<string>  $startTimesLocal  One slot for a single booking, several for a package booking.
     */
    public function __construct(
        public int $tutorProfileId,
        public int $bookerUserId,
        public array $startTimesLocal,
        public string $packageCode = 'single',
        public ?string $notes = null,
        public ?string $studentName = null,
        public ?string $studentPhone = null,
    ) {}

    /**
     * Convenience factory for a single-slot booking.
     */
    public static function single(
        int $tutorProfileId,
        int $bookerUserId,
        string $startTimeLocal,
        string $packageCode = 'single',
        ?string $notes = null,
        ?string $studentName = null,
        ?string $studentPhone = null,
    ): self {
        return new self(
            tutorProfileId: $tutorProfileId,
            bookerUserId: $bookerUserId,
            startTimesLocal: [$startTimeLocal],
            packageCode: $packageCode,
            notes: $notes,
            studentName: $studentName,
            studentPhone: $studentPhone,
        );
    }

    /**
     * True when more than one slot was supplied (package booking).
     */
    public function isPackage(): bool
    {
        return count($this->startTimesLocal) > 1;
    }

    /**
     * The first (or only) requested slot.
     */
    public function firstSlot(): string
    {
        return (string) ($this->startTimesLocal[0] ?? '');
    }
}
