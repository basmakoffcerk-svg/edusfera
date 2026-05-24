<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\DiagnosticPage;
use App\Models\StudentGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiagnosticPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_open_diagnostic_page(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375297000001',
        ]);

        $this->actingAs($student)
            ->get('/admin/diagnostic')
            ->assertOk()
            ->assertSee('Стартовая диагностика');
    }

    public function test_student_can_save_baseline_diagnostic(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375297000002',
        ]);

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375297000003',
        ]);

        $goal = StudentGoal::query()->create([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'Белорусский язык',
            'exam_type' => 'ЦЭ',
            'status' => 'active',
        ]);

        Livewire::actingAs($student)
            ->test(DiagnosticPage::class)
            ->set('selectedGoalId', $goal->id)
            ->set('examType', 'ЦЭ')
            ->set('currentScore', 43)
            ->set('targetScore', 78)
            ->set('examDate', now()->addMonths(3)->format('Y-m-d'))
            ->set('weakTopics', ['Орфография', 'Синтаксис'])
            ->set('notes', 'Нужен сильный упор на тестовые ловушки.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('diagnostic_attempts', [
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'subject' => 'Белорусский язык',
            'exam_type' => 'ЦЭ',
            'score' => 43,
            'source' => 'self_assessment',
        ]);

        $this->assertDatabaseHas('skill_gaps', [
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'topic' => 'Орфография',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('progress_snapshots', [
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'current_score' => 43,
            'predicted_score' => 43,
            'target_score' => 78,
            'active_skill_gaps_count' => 2,
        ]);
    }
}
