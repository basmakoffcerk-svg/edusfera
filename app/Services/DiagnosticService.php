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
     * Record completed AI diagnostic attempt with RIKZ evaluation.
     *
     * @param  array<string, mixed>  $evalResult
     * @param  array<string, mixed>  $answers
     */
    public function recordAiDiagnostic(
        StudentGoal $goal,
        int $studentId,
        array $evalResult,
        array $answers,
    ): DiagnosticAttempt {
        if ($goal->student_id !== $studentId) {
            throw ValidationException::withMessages([
                'goal' => 'Эта цель недоступна для текущего пользователя.',
            ]);
        }

        return DB::transaction(function () use ($answers, $evalResult, $goal, $studentId): DiagnosticAttempt {
            $scaledScore = (int) ($evalResult['scaled_score'] ?? 0);
            $targetScore = isset($evalResult['target_score']) ? (int) $evalResult['target_score'] : $goal->target_score;

            $goal->update([
                'current_score' => $scaledScore,
                'target_score' => $targetScore,
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
                'source' => 'ai_diagnostic',
                'score' => $scaledScore,
                'max_score' => 100,
                'taken_at' => now('UTC'),
                'breakdown' => [
                    'accuracy_percent' => $evalResult['accuracy_percent'] ?? 0,
                    'correct_count' => $evalResult['correct_count'] ?? 0,
                    'incorrect_count' => $evalResult['incorrect_count'] ?? 0,
                    'pass_probability_percent' => $evalResult['pass_probability_percent'] ?? 0,
                    'earned_primary_score' => $evalResult['earned_primary_score'] ?? 0,
                    'max_primary_score' => $evalResult['max_primary_score'] ?? 0,
                    'skill_gaps' => $evalResult['skill_gaps'] ?? [],
                    'cognitive_profile' => $evalResult['cognitive_profile'] ?? [],
                    'study_plan' => $evalResult['study_plan'] ?? [],
                    'tutor_recommendations' => $evalResult['tutor_recommendations'] ?? [],
                    'answers' => $answers,
                ],
                'notes' => $evalResult['notes'] ?? null,
            ]);

            SkillGap::query()
                ->where('student_goal_id', $goal->id)
                ->where('student_id', $studentId)
                ->where('status', 'open')
                ->update(['status' => 'resolved']);

            $gaps = (array) ($evalResult['skill_gaps'] ?? []);
            foreach ($gaps as $gap) {
                SkillGap::query()->create([
                    'student_goal_id' => $goal->id,
                    'diagnostic_attempt_id' => $attempt->id,
                    'student_id' => $studentId,
                    'subject' => $goal->subject,
                    'topic' => $gap['topic'] ?? 'Неизвестная тема',
                    'severity' => $gap['severity'] ?? 'medium',
                    'status' => 'open',
                    'last_detected_at' => now('UTC'),
                    'evidence' => [
                        'source' => 'ai_diagnostic',
                        'question_id' => $gap['question_id'] ?? null,
                        'part' => $gap['part'] ?? null,
                        'potential_score_loss' => $gap['potential_score_loss'] ?? null,
                        'trap_analysis' => $gap['trap_analysis'] ?? null,
                        'cognitive_bias' => $gap['cognitive_bias'] ?? null,
                    ],
                ]);
            }

            $summary = "ИИ-диагностика по предмету «{$goal->subject}» завершена. Прогнозируемый балл: {$scaledScore}/100.";
            if ($targetScore !== null) {
                $summary .= " Цель: {$targetScore} баллов.";
            }
            if (count($gaps) > 0) {
                $gapTopics = array_unique(array_column($gaps, 'topic'));
                $summary .= ' Выявлены пробелы: ' . implode(', ', array_slice($gapTopics, 0, 3)) . '.';
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
                    'current_score' => $scaledScore,
                    'predicted_score' => $scaledScore,
                    'target_score' => $targetScore,
                    'completed_topics_count' => 0,
                    'active_skill_gaps_count' => count($gaps),
                    'summary' => $summary,
                    'meta' => [
                        'source' => 'ai_diagnostic',
                        'diagnostic_attempt_id' => $attempt->id,
                        'accuracy_percent' => $evalResult['accuracy_percent'] ?? 0,
                        'pass_probability_percent' => $evalResult['pass_probability_percent'] ?? null,
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
