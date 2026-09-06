<?php

declare(strict_types=1);

namespace App\Contracts\Lesson;

use App\Domain\Lesson\Dto\LessonDto;

/**
 * Read-side contract for the Lesson bounded context.
 *
 * Consumers (Filament resources, API controllers) depend on this interface
 * instead of the Eloquent model, so the persistence layer never crosses the
 * context boundary (Requirement 6.2).
 */
interface LessonReader
{
    /**
     * Find a single lesson by id, or null when it does not exist.
     */
    public function find(int $id): ?LessonDto;

    /**
     * All lessons relevant to the given user (as tutor, student or parent).
     *
     * @return list<LessonDto>
     */
    public function forUser(int $userId): array;
}
