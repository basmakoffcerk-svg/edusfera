<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\SlotUnavailableException;
use App\Models\Lesson;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\LessonBookedStudentNotification;
use App\Notifications\LessonBookedTutorNotification;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_booking_and_price_is_fixed(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291111111',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292222222',
        ]);

        $profile = TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'subjects' => ['Математика'],
            'price_per_hour' => 100,
            'experience_years' => 5,
            'legal_status' => 'ip',
            'bio' => 'Опытный преподаватель.',
            'is_verified' => true,
            'rating_avg' => 4.9,
        ]);

        $slot = CarbonImmutable::now(config('booking.display_timezone'))
            ->addDays(2)
            ->setTime(12, 0);

        TutorAvailability::query()->create([
            'user_id' => $tutor->id,
            'day_of_week' => $slot->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'is_active' => true,
        ]);

        $lesson = app(BookingService::class)->createBooking(
            tutorProfile: $profile,
            booker: $student,
            startTimeLocal: $slot->format('Y-m-d H:i'),
            notes: 'Подготовка к экзамену',
            studentName: 'Иван Ученик',
            studentPhone: '+375299999999',
        );

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => Lesson::STATUS_PENDING,
            'price' => '100.00',
            'platform_commission' => '15.00',
            'net_amount' => '85.00',
            'package_code' => 'single',
            'package_lessons' => 1,
            'package_total' => '100.00',
            'package_discount' => '0.00',
        ]);

        Notification::assertSentTo($student, LessonBookedStudentNotification::class);
        Notification::assertSentTo($tutor, LessonBookedTutorNotification::class);
    }

    public function test_booking_service_blocks_double_booking(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375293333333',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375294444444',
        ]);

        $anotherStudent = User::factory()->create([
            'role' => 'student',
            'phone' => '+375295555555',
        ]);

        $profile = TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'subjects' => ['Физика'],
            'price_per_hour' => 80,
            'experience_years' => 7,
            'legal_status' => 'npd',
            'bio' => 'Провожу занятия онлайн.',
            'is_verified' => true,
            'rating_avg' => 5,
        ]);

        $slot = CarbonImmutable::now(config('booking.display_timezone'))
            ->addDays(2)
            ->setTime(15, 0);

        TutorAvailability::query()->create([
            'user_id' => $tutor->id,
            'day_of_week' => $slot->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'is_active' => true,
        ]);

        Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $slot->utc(),
            'end_time' => $slot->addHour()->utc(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
        ]);

        $this->expectException(SlotUnavailableException::class);

        app(BookingService::class)->createBooking(
            tutorProfile: $profile,
            booker: $anotherStudent,
            startTimeLocal: $slot->format('Y-m-d H:i'),
        );
    }

    public function test_booking_stores_selected_package_fields(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375296111111',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375296222222',
        ]);

        $profile = TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'subjects' => ['Английский язык'],
            'price_per_hour' => 50,
            'experience_years' => 3,
            'legal_status' => 'self_employed',
            'bio' => 'Подготовка к экзаменам.',
            'is_verified' => true,
            'rating_avg' => 4.7,
        ]);

        $slot = CarbonImmutable::now(config('booking.display_timezone'))
            ->addDays(3)
            ->setTime(11, 0);

        TutorAvailability::query()->create([
            'user_id' => $tutor->id,
            'day_of_week' => $slot->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '19:00:00',
            'is_active' => true,
        ]);

        $lesson = app(BookingService::class)->createBooking(
            tutorProfile: $profile,
            booker: $student,
            startTimeLocal: $slot->format('Y-m-d H:i'),
            packageCode: 'pack_4',
        );

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'package_code' => 'pack_4',
            'package_lessons' => 4,
            'package_lessons_remaining' => 4,
            'package_total' => '190.00',
            'package_discount' => '10.00',
            'platform_commission' => '28.50',
            'net_amount' => '161.50',
        ]);
    }

    public function test_package_booking_reserves_all_selected_slots(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375296999991',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375296999992',
        ]);

        $profile = TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'subjects' => ['Химия'],
            'price_per_hour' => 60,
            'experience_years' => 4,
            'legal_status' => 'ip',
            'bio' => 'Готовлю к тестам.',
            'is_verified' => true,
            'rating_avg' => 4.9,
        ]);

        $date = CarbonImmutable::now(config('booking.display_timezone'))
            ->addDays(5)
            ->startOfDay();

        TutorAvailability::query()->create([
            'user_id' => $tutor->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        $lesson = app(BookingService::class)->createPackageBooking(
            tutorProfile: $profile,
            booker: $student,
            startTimesLocal: [
                $date->setTime(10, 0)->format('Y-m-d H:i'),
                $date->setTime(11, 0)->format('Y-m-d H:i'),
                $date->setTime(12, 0)->format('Y-m-d H:i'),
                $date->setTime(13, 0)->format('Y-m-d H:i'),
            ],
            packageCode: 'pack_4',
        );

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'package_code' => 'pack_4',
            'package_lessons' => 4,
            'package_lessons_remaining' => 4,
            'package_total' => '228.00',
        ]);

        $this->assertSame(3, Lesson::query()->where('package_parent_lesson_id', $lesson->id)->count());
    }

    public function test_available_slots_ignore_lessons_with_expired_payment_locks(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375296333333',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375296444444',
        ]);

        $profile = TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'subjects' => ['Информатика'],
            'price_per_hour' => 70,
            'experience_years' => 4,
            'legal_status' => 'ip',
            'bio' => 'Готовлю к олимпиадам.',
            'is_verified' => true,
            'rating_avg' => 4.8,
        ]);

        $date = CarbonImmutable::now(config('booking.display_timezone'))
            ->addDays(3)
            ->startOfDay();

        TutorAvailability::query()->create([
            'user_id' => $tutor->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);

        Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $date->setTime(10, 0)->utc(),
            'end_time' => $date->setTime(11, 0)->utc(),
            'duration_minutes' => 60,
            'price' => '70.00',
            'platform_commission' => '10.50',
            'net_amount' => '59.50',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => CarbonImmutable::now('UTC')->subMinute(),
        ]);

        $slots = app(BookingService::class)->getAvailableSlots($profile, $date);

        $this->assertContains($date->setTime(10, 0)->format('Y-m-d H:i'), array_column($slots, 'value'));
    }

    public function test_available_slots_block_lessons_that_overlap_selected_day_boundary(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375296555555',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375296777777',
        ]);

        $profile = TutorProfile::query()->create([
            'user_id' => $tutor->id,
            'subjects' => ['Математика'],
            'price_per_hour' => 90,
            'experience_years' => 6,
            'legal_status' => 'ip',
            'bio' => 'Работаю с сильными учениками.',
            'is_verified' => true,
            'rating_avg' => 4.9,
        ]);

        $date = CarbonImmutable::now(config('booking.display_timezone'))
            ->addDays(4)
            ->startOfDay();

        TutorAvailability::query()->create([
            'user_id' => $tutor->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '00:00:00',
            'end_time' => '02:00:00',
            'is_active' => true,
        ]);

        Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $date->subMinutes(30)->utc(),
            'end_time' => $date->addMinutes(30)->utc(),
            'duration_minutes' => 60,
            'price' => '90.00',
            'platform_commission' => '13.50',
            'net_amount' => '76.50',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $slots = app(BookingService::class)->getAvailableSlots($profile, $date);

        $this->assertNotContains($date->format('Y-m-d H:i'), array_column($slots, 'value'));
        $this->assertContains($date->setTime(1, 0)->format('Y-m-d H:i'), array_column($slots, 'value'));
    }
}
