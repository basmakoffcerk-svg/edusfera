<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LessonResource\Pages\ListLessons;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TutorGoogleCalendarScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_sees_calendar_mode_by_default_with_week_hours_and_lessons(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298400001',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298400002',
        ]);

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $nowLocal = Carbon::now($displayTz)->startOfWeek()->addHours(14); // Monday 14:00

        $lesson = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $nowLocal->copy()->utc(),
            'end_time' => $nowLocal->copy()->addHour()->utc(),
            'duration_minutes' => 60,
            'price' => '75.00',
            'platform_commission' => '0.00',
            'net_amount' => '75.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        Livewire::actingAs($tutor)
            ->test(ListLessons::class)
            ->assertSet('viewMode', 'calendar')
            ->assertSee('Календарь')
            ->assertSee('Таблица')
            ->assertSee('GMT+3')
            ->assertSee('08:00')
            ->assertSee('14:00')
            ->assertSee($student->name)
            ->assertSee('Синхронизация (.ics)');
    }

    public function test_tutor_can_navigate_weeks_and_switch_to_table_mode(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298400003',
        ]);

        $displayTz = config('booking.display_timezone', 'Europe/Minsk');
        $initialWeekStart = Carbon::now($displayTz)->startOfWeek()->format('Y-m-d');

        Livewire::actingAs($tutor)
            ->test(ListLessons::class)
            ->assertSet('currentWeekStart', $initialWeekStart)
            ->call('nextWeek')
            ->assertSet('currentWeekStart', Carbon::parse($initialWeekStart)->addWeek()->format('Y-m-d'))
            ->call('previousWeek')
            ->assertSet('currentWeekStart', $initialWeekStart)
            ->call('setViewMode', 'table')
            ->assertSet('viewMode', 'table');
    }

    public function test_tutor_can_export_ics_calendar_file(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298400004',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298400005',
        ]);

        Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now('UTC')->addDays(2),
            'end_time' => now('UTC')->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => '50.00',
            'platform_commission' => '0.00',
            'net_amount' => '50.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        $component = Livewire::actingAs($tutor)->test(ListLessons::class);
        $response = $component->instance()->exportIcs();

        $this->assertEquals('text/calendar; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('edusfera-schedule.ics', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_tutor_can_create_lesson_manually_and_mark_paid_directly(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375298400010',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375298400011',
        ]);

        $startTime = now()->addDays(1)->startOfHour()->format('Y-m-d H:i:s');

        Livewire::actingAs($tutor)
            ->test(ListLessons::class)
            ->assertActionVisible('create_lesson')
            ->callAction('create_lesson', [
                'student_mode' => 'existing',
                'student_id' => $student->id,
                'subject' => 'Физика',
                'start_time' => $startTime,
                'duration_minutes' => 60,
                'price' => '45.00',
                'payment_status' => Lesson::PAYMENT_UNPAID,
                'notes' => 'Подготовка к ЦТ',
            ]);

        $lesson = Lesson::query()->where('tutor_id', $tutor->id)->where('student_id', $student->id)->first();
        $this->assertNotNull($lesson);
        $this->assertStringContainsString('Физика', (string) $lesson->notes);
        $this->assertEquals('45.00', $lesson->price);
        $this->assertEquals(Lesson::PAYMENT_UNPAID, $lesson->payment_status);

        // Mark directly paid
        Livewire::actingAs($tutor)
            ->test(ListLessons::class)
            ->call('markLessonPaidDirectly', $lesson->id);

        $this->assertEquals(Lesson::PAYMENT_PAID, $lesson->fresh()->payment_status);
    }
}
