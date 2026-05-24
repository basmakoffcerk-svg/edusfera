<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\HomeworkPage;
use App\Models\HomeworkAssignment;
use App\Models\StudentGoal;
use App\Models\User;
use App\Services\HomeworkService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeworkPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_open_homework_page(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298300001',
        ]);

        $this->actingAs($student)
            ->get('/admin/homework')
            ->assertOk()
            ->assertSee('Домашние задания');
    }

    public function test_student_can_mark_homework_completed(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298300002',
        ]);

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298300003',
        ]);

        $goal = StudentGoal::query()->create([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'Белорусский язык',
            'exam_type' => 'ЦЭ',
            'status' => 'active',
        ]);

        $assignment = HomeworkAssignment::query()->create([
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'title' => 'Домашняя работа после урока',
            'instructions' => 'Решить 10 заданий по синтаксису.',
            'source' => 'tutor',
            'status' => 'assigned',
            'assigned_at' => CarbonImmutable::now('UTC'),
            'due_at' => CarbonImmutable::now('UTC')->addDays(2),
            'payload' => [
                'focus' => 'Синтаксис',
                'next_step' => 'Разобрать сложные конструкции',
            ],
        ]);

        Livewire::actingAs($student)
            ->test(HomeworkPage::class)
            ->call('completeAssignment', $assignment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('homework_assignments', [
            'id' => $assignment->id,
            'status' => 'completed',
        ]);
    }

    public function test_homework_service_rejects_foreign_student(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298300004',
        ]);

        $otherStudent = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298300005',
        ]);

        $assignment = HomeworkAssignment::query()->create([
            'student_id' => $student->id,
            'title' => 'Тестовое задание',
            'source' => 'tutor',
            'status' => 'assigned',
            'assigned_at' => CarbonImmutable::now('UTC'),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(HomeworkService::class)->markCompleted($assignment, $otherStudent->id);
    }
}
