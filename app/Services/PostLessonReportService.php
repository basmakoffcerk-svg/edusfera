<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\ProgressSnapshot;
use App\Models\StudentGoal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostLessonReportService
{
    public function saveReport(
        Lesson $lesson,
        int $actorId,
        string $summary,
        string $focus,
        string $nextStep,
        ?string $homework,
        ?int $scoreEstimate,
    ): Lesson {
        if (! in_array($actorId, [$lesson->tutor_id], true)) {
            throw ValidationException::withMessages([
                'report' => 'Отчёт после урока может заполнить только преподаватель этого занятия.',
            ]);
        }

        return DB::transaction(function () use ($focus, $homework, $lesson, $nextStep, $scoreEstimate, $summary): Lesson {
            $lesson = Lesson::query()->with(['student', 'tutor'])->lockForUpdate()->findOrFail($lesson->id);

            $lesson->update([
                'tutor_report_summary' => trim($summary),
                'tutor_report_focus' => trim($focus),
                'tutor_next_step' => trim($nextStep),
                'tutor_homework_summary' => $homework !== null && trim($homework) !== '' ? trim($homework) : null,
                'tutor_report_score' => $scoreEstimate,
                'tutor_reported_at' => now('UTC'),
            ]);

            $goal = StudentGoal::query()
                ->where('student_id', $lesson->student_id)
                ->where('tutor_id', $lesson->tutor_id)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if ($goal) {
                $snapshot = ProgressSnapshot::query()->updateOrCreate(
                    [
                        'student_goal_id' => $goal->id,
                        'snapshot_date' => now(config('booking.display_timezone'))->toDateString(),
                    ],
                    [
                        'exam_track_id' => $goal->examTracks()->where('status', 'active')->latest('id')->value('id'),
                        'student_id' => $lesson->student_id,
                        'tutor_id' => $lesson->tutor_id,
                        'current_score' => $scoreEstimate ?? $goal->current_score,
                        'predicted_score' => $scoreEstimate ?? $goal->current_score,
                        'target_score' => $goal->target_score,
                        'active_skill_gaps_count' => $goal->skillGaps()->where('status', 'open')->count(),
                        'summary' => trim($nextStep) !== '' ? trim($nextStep) : trim($summary),
                        'meta' => [
                            'source' => 'tutor_report',
                            'lesson_id' => $lesson->id,
                        ],
                    ],
                );

                if ($scoreEstimate !== null) {
                    $goal->update([
                        'current_score' => $scoreEstimate,
                    ]);
                }

                if ($homework !== null && trim($homework) !== '') {
                    HomeworkAssignment::query()->updateOrCreate(
                        [
                            'lesson_id' => $lesson->id,
                            'student_id' => $lesson->student_id,
                        ],
                        [
                            'student_goal_id' => $goal->id,
                            'tutor_id' => $lesson->tutor_id,
                            'title' => 'Домашняя работа после урока',
                            'instructions' => trim($homework),
                            'source' => 'tutor',
                            'status' => 'assigned',
                            'assigned_at' => now('UTC'),
                            'due_at' => $lesson->start_time->copy()->addDays(3),
                            'payload' => [
                                'lesson_id' => $lesson->id,
                                'focus' => trim($focus),
                                'next_step' => trim($nextStep),
                                'snapshot_id' => $snapshot->id,
                            ],
                        ],
                    );
                }
            }

            return $lesson->fresh(['homeworkAssignments']);
        });
    }
}
