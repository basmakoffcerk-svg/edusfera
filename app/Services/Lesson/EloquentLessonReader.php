<?php

declare(strict_types=1);

namespace App\Services\Lesson;

use App\Contracts\Lesson\LessonReader;
use App\Domain\Lesson\Dto\LessonDto;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent-backed implementation of the {@see LessonReader} contract.
 *
 * This is the only Lesson read path allowed to touch the Eloquent model; all
 * results leave the context as {@see LessonDto} (Requirement 6.2, 6.6).
 */
final class EloquentLessonReader implements LessonReader
{
    public function find(int $id): ?LessonDto
    {
        $lesson = Lesson::query()->find($id);

        return $lesson === null ? null : LessonDtoMapper::fromModel($lesson);
    }

    public function forUser(int $userId): array
    {
        return Lesson::query()
            ->where(function (Builder $query) use ($userId): void {
                $query
                    ->where('tutor_id', $userId)
                    ->orWhere('student_id', $userId)
                    ->orWhere('parent_id', $userId);
            })
            ->orderBy('start_time')
            ->get()
            ->map(static fn (Lesson $lesson): LessonDto => LessonDtoMapper::fromModel($lesson))
            ->all();
    }
}
