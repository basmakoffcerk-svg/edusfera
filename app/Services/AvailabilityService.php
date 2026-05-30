<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TutorAvailability;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    /**
     * Validate that the new availability window does not overlap with existing windows
     * for the same tutor on the same day.
     *
     * @throws ValidationException
     */
    public function validateNoOverlap(
        int $userId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeId = null,
    ): void {
        $query = TutorAvailability::query()
            ->where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($inner) use ($startTime, $endTime) {
                    $inner->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_time' => 'Выбранное время пересекается с существующим окном доступности.',
            ]);
        }
    }

    /**
     * Create a new availability window with overlap validation.
     */
    public function createWindow(int $userId, int $dayOfWeek, string $startTime, string $endTime): TutorAvailability
    {
        $this->validateNoOverlap($userId, $dayOfWeek, $startTime, $endTime);

        return TutorAvailability::query()->create([
            'user_id' => $userId,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => true,
        ]);
    }

    /**
     * Update an existing availability window with overlap validation.
     */
    public function updateWindow(TutorAvailability $window, int $dayOfWeek, string $startTime, string $endTime): TutorAvailability
    {
        $this->validateNoOverlap($window->user_id, $dayOfWeek, $startTime, $endTime, $window->id);

        $window->update([
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        return $window;
    }
}
