<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DiagnosticAttempt;
use App\Models\ProgressSnapshot;
use App\Models\SkillGap;
use App\Models\StudentGoal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DiagnosticService
{
    /**
     * @param  array<int, string>  $weakTopics
     */
    public function recordBaseline(
        StudentGoal $goal,
        int $studentId,
        ?int $currentScore,
        ?int $targetScore,
        ?string $examDate,
        array $weakTopics,
        ?string $notes = null,
    ): DiagnosticAttempt {
        if ($goal->student_id !== $studentId) {
            throw ValidationException::withMessages([
                'goal' => 'Эта цель недоступна для текущего пользователя.',
            ]);
        }

        return DB::transaction(function () use ($currentScore, $examDate, $goal, $notes, $studentId, $targetScore, $weakTopics): DiagnosticAttempt {
            $goal->update([
                'current_score' => $currentScore,
                'target_score' => $targetScore,
                'exam_date' => $examDate,
                'latest_diagnostic_at' => now('UTC'),
                'status' => 'active',
            ]);

            $track = $goal->examTracks()
                ->where('status', 'active')
                ->latest('id')
                ->first();

            $attempt = DiagnosticAttempt::query()->create([
                'student_goal_id' => $goal->id,
                'exam_track_id' => $track?->id,
                'student_id' => $studentId,
                'tutor_id' => $goal->tutor_id,
                'subject' => $goal->subject,
                'exam_type' => $goal->exam_type,
                'source' => 'self_assessment',
                'score' => $currentScore,
                'max_score' => 100,
                'taken_at' => now('UTC'),
                'breakdown' => [
                    'weak_topics' => array_values($weakTopics),
                ],
                'notes' => $notes,
            ]);

            SkillGap::query()
                ->where('student_goal_id', $goal->id)
                ->where('student_id', $studentId)
                ->where('status', 'open')
                ->update(['status' => 'resolved']);

            foreach ($weakTopics as $topic) {
                SkillGap::query()->create([
                    'student_goal_id' => $goal->id,
                    'diagnostic_attempt_id' => $attempt->id,
                    'student_id' => $studentId,
                    'subject' => $goal->subject,
                    'topic' => $topic,
                    'severity' => 'medium',
                    'status' => 'open',
                    'last_detected_at' => now('UTC'),
                    'evidence' => [
                        'source' => 'self_assessment',
                    ],
                ]);
            }

            ProgressSnapshot::query()->updateOrCreate(
                [
                    'student_goal_id' => $goal->id,
                    'snapshot_date' => now(config('booking.display_timezone'))->toDateString(),
                ],
                [
                    'exam_track_id' => $track?->id,
                    'student_id' => $studentId,
                    'tutor_id' => $goal->tutor_id,
                    'current_score' => $currentScore,
                    'predicted_score' => $currentScore,
                    'target_score' => $targetScore,
                    'completed_topics_count' => 0,
                    'active_skill_gaps_count' => count($weakTopics),
                    'summary' => $this->buildSummary($goal->subject, $currentScore, $targetScore, $weakTopics),
                    'meta' => [
                        'source' => 'self_assessment',
                        'diagnostic_attempt_id' => $attempt->id,
                    ],
                ],
            );

            return $attempt;
        });
    }

    /**
     * @param  array<int, string>  $weakTopics
     */
    private function buildSummary(string $subject, ?int $currentScore, ?int $targetScore, array $weakTopics): string
    {
        $parts = ["Стартовая диагностика по предмету «{$subject}» сохранена."];

        if ($currentScore !== null) {
            $parts[] = "Текущий ориентир: {$currentScore} баллов.";
        }

        if ($targetScore !== null) {
            $parts[] = "Цель: {$targetScore} баллов.";
        }

        if ($weakTopics !== []) {
            $parts[] = 'В фокусе: '.implode(', ', $weakTopics).'.';
        }

        return implode(' ', $parts);
    }
}
