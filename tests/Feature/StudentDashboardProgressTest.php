<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProgressSnapshot;
use App\Models\SkillGap;
use App\Models\StudentGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentDashboardProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_shows_progress_block_for_active_goal(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298100001',
        ]);

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298100002',
        ]);

        $goal = StudentGoal::query()->create([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'Белорусский язык',
            'exam_type' => 'ЦЭ',
            'current_score' => 48,
            'target_score' => 80,
            'exam_date' => now()->addMonths(4)->toDateString(),
            'status' => 'active',
            'latest_diagnostic_at' => now('UTC'),
        ]);

        SkillGap::query()->create([
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'subject' => 'Белорусский язык',
            'topic' => 'Орфография',
            'severity' => 'medium',
            'status' => 'open',
            'last_detected_at' => now('UTC'),
        ]);

        ProgressSnapshot::query()->create([
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'snapshot_date' => now()->toDateString(),
            'current_score' => 48,
            'predicted_score' => 61,
            'target_score' => 80,
            'completed_topics_count' => 2,
            'active_skill_gaps_count' => 1,
            'summary' => 'Стартовая диагностика сохранена. В фокусе орфография.',
        ]);

        Livewire::actingAs($student)
            ->test(\App\Filament\Widgets\StudentWelcomeWidget::class)
            ->assertSee('Учебный прогресс')
            ->assertSee('Белорусский язык')
            ->assertSee('Прогноз')
            ->assertSee('61')
            ->assertSee('Слабые темы');
    }
}
