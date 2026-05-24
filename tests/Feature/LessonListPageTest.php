<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LessonResource\Pages\ListLessons;
use App\Filament\Resources\LessonResource\Widgets\LessonOverviewWidget;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LessonListPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_lesson_list_shows_overview_tabs_and_actions(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298200001',
        ]);

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298200002',
        ]);

        Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now('UTC')->addDay(),
            'end_time' => now('UTC')->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        Livewire::actingAs($student)
            ->test(ListLessons::class)
            ->assertSee('Требуют внимания')
            ->assertSee('Ближайшие')
            ->assertSee($tutor->name)
            ->assertSee('Оплатить урок');

        Livewire::actingAs($student)
            ->test(LessonOverviewWidget::class)
            ->assertSee('Ближайший урок')
            ->assertSee('Требуют действия')
            ->assertSee('1 к оплате');
    }
}
