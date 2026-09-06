<?php

declare(strict_types=1);

namespace App\Domain\Lesson\Dto;

/**
 * Readonly transport object for the Lesson aggregate.
 *
 * Intentionally free of any Eloquent dependency — mapping from the persistence
 * model lives in the Lesson services (see App\Services\Lesson\LessonDtoMapper),
 * keeping the bounded-context boundary clean (Requirement 6.2, 6.6).
 */
final readonly class LessonDto
{
    public function __construct(
        public int $id,
        public int $tutorId,
        public int $studentId,
        public ?int $parentId,
        public string $startTime,
        public string $endTime,
        public int $durationMinutes,
        public string $price,
        public string $status,
        public string $paymentStatus,
        public ?string $packageCode,
        public int $packageLessons,
        public ?int $packageParentLessonId,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tutor_id' => $this->tutorId,
            'student_id' => $this->studentId,
            'parent_id' => $this->parentId,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'duration_minutes' => $this->durationMinutes,
            'price' => $this->price,
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
            'package_code' => $this->packageCode,
            'package_lessons' => $this->packageLessons,
            'package_parent_lesson_id' => $this->packageParentLessonId,
        ];
    }
}
