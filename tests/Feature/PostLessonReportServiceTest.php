<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExamTrack;
use App\Models\HomeworkAssignment;
use App\Models\Lesson;
use App\Models\ProgressSnapshot;
use App\Models\SkillGap;
use App\Models\StudentGoal;
use App\Models\User;
use App\Services\PostLessonReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostLessonReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_can_save_post_lesson_report_and_create_homework(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298200001',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298200002',
        ]);

        $goal = StudentGoal::query()->create([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'Белорусский язык',
            'exam_type' => 'ЦЭ',
            'current_score' => 44,
            'target_score' => 78,
            'status' => 'active',
            'latest_diagnostic_at' => now('UTC'),
        ]);

        ExamTrack::query()->create([
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'title' => 'Траектория подготовки',
            'format' => 'individual',
            'status' => 'active',
        ]);

        SkillGap::query()->create([
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'subject' => 'Белорусский язык',
            'topic' => 'Синтаксис',
            'severity' => 'medium',
            'status' => 'open',
            'last_detected_at' => now('UTC'),
        ]);

        $lesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => CarbonImmutable::now('UTC')->subHours(2),
            'end_time' => CarbonImmutable::now('UTC')->subHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '6.00',
            'net_amount' => '34.00',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        app(PostLessonReportService::class)->saveReport(
            lesson: $lesson,
            actorId: $tutor->id,
            summary: 'Разобрали тестовую часть и повторили орфографию.',
            focus: 'Нужно укрепить синтаксис и темп решения.',
            nextStep: 'Следующий урок посвятить сложным синтаксическим конструкциям.',
            homework: 'Выполнить 15 заданий на синтаксис и прислать вопросы.',
            scoreEstimate: 51,
        );

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'tutor_report_score' => 51,
        ]);
        $this->assertDatabaseHas('homework_assignments', [
            'lesson_id' => $lesson->id,
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'source' => 'tutor',
            'status' => 'assigned',
        ]);
        $this->assertDatabaseHas('progress_snapshots', [
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'current_score' => 51,
            'predicted_score' => 51,
            'target_score' => 78,
            'active_skill_gaps_count' => 1,
        ]);

        $goal->refresh();
        $this->assertSame(51, $goal->current_score);
        $this->assertSame(1, HomeworkAssignment::query()->count());
        $this->assertSame(1, ProgressSnapshot::query()->count());
    }
}
