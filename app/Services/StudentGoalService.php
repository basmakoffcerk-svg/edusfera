<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExamTrack;
use App\Models\Lesson;
use App\Models\StudentGoal;

class StudentGoalService
{
    public function ensureGoalForPaidLesson(Lesson $lesson): StudentGoal
    {
        $lesson->loadMissing('tutor.tutorProfile', 'student');

        $tutorProfile = $lesson->tutor?->tutorProfile;
        $subject = $tutorProfile?->subjects[0] ?? 'Не указан';
        $examType = $this->resolveExamType($tutorProfile?->audiences ?? []);

        $goal = StudentGoal::query()->firstOrCreate(
            [
                'student_id' => $lesson->student_id,
                'tutor_id' => $lesson->tutor_id,
                'subject' => $subject,
                'exam_type' => $examType,
                'status' => 'active',
            ],
            [
                'meta' => [
                    'source' => 'lesson_payment',
                    'created_from_lesson_id' => $lesson->id,
                    'created_from_package_code' => $lesson->package_code,
                ],
            ],
        );

        ExamTrack::query()->firstOrCreate(
            [
                'student_goal_id' => $goal->id,
                'student_id' => $lesson->student_id,
                'tutor_id' => $lesson->tutor_id,
                'status' => 'active',
            ],
            [
                'title' => $this->buildTrackTitle($subject, $examType),
                'format' => $this->resolveFormat($tutorProfile?->lesson_formats ?? []),
                'weekly_sessions_target' => $this->resolveWeeklySessionsTarget($lesson->package_lessons),
                'starts_on' => $lesson->start_time?->clone()->setTimezone(config('booking.display_timezone'))->toDateString(),
                'ends_on' => $lesson->start_time?->clone()->setTimezone(config('booking.display_timezone'))->addWeeks($this->resolveDurationWeeks($lesson->package_lessons))->toDateString(),
                'meta' => [
                    'source' => 'lesson_payment',
                    'created_from_lesson_id' => $lesson->id,
                    'package_code' => $lesson->package_code,
                ],
            ],
        );

        return $goal->fresh(['examTracks']);
    }

    /**
     * @param array<int, string> $audiences
     */
    private function resolveExamType(array $audiences): string
    {
        if (in_array('Подготовка к ЦТ', $audiences, true)) {
            return 'ЦТ';
        }

        if (in_array('Подготовка к ЦЭ', $audiences, true)) {
            return 'ЦЭ';
        }

        return 'ЦЭ';
    }

    /**
     * @param array<int, string> $lessonFormats
     */
    private function resolveFormat(array $lessonFormats): string
    {
        if (in_array('intensive', $lessonFormats, true)) {
            return 'intensive';
        }

        if (in_array('mini_group_online', $lessonFormats, true)) {
            return 'mini_group';
        }

        return 'individual';
    }

    private function resolveWeeklySessionsTarget(?int $packageLessons): int
    {
        if ($packageLessons === null || $packageLessons <= 1) {
            return 1;
        }

        return min($packageLessons, 2);
    }

    private function resolveDurationWeeks(?int $packageLessons): int
    {
        if ($packageLessons === null || $packageLessons <= 1) {
            return 4;
        }

        return max($packageLessons, 4);
    }

    private function buildTrackTitle(string $subject, string $examType): string
    {
        return "Траектория подготовки к {$examType}: {$subject}";
    }
}
