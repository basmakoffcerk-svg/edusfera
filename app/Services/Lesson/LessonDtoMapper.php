<?php

declare(strict_types=1);

namespace App\Services\Lesson;

use App\Domain\Lesson\Dto\LessonDto;
use App\Models\Lesson;

/**
 * Maps the Eloquent {@see Lesson} model into the persistence-free
 * {@see LessonDto}. Centralising the mapping here keeps the DTO itself free of
 * any Eloquent dependency (Requirement 6.6).
 *
 * @internal Used by the Lesson services only.
 */
final class LessonDtoMapper
{
    public static function fromModel(Lesson $lesson): LessonDto
    {
        return new LessonDto(
            id: (int) $lesson->id,
            tutorId: (int) $lesson->tutor_id,
            studentId: (int) $lesson->student_id,
            parentId: $lesson->parent_id !== null ? (int) $lesson->parent_id : null,
            startTime: $lesson->start_time?->toIso8601String() ?? '',
            endTime: $lesson->end_time?->toIso8601String() ?? '',
            durationMinutes: (int) $lesson->duration_minutes,
            price: (string) $lesson->price,
            status: (string) $lesson->status,
            paymentStatus: (string) $lesson->payment_status,
            packageCode: $lesson->package_code !== null ? (string) $lesson->package_code : null,
            packageLessons: (int) $lesson->package_lessons,
            packageParentLessonId: $lesson->package_parent_lesson_id !== null
                ? (int) $lesson->package_parent_lesson_id
                : null,
        );
    }
}
