<?php

declare(strict_types=1);

namespace Tests\Feature\Lesson;

use App\Contracts\Lesson\LessonBooker;
use App\Contracts\Lesson\LessonReader;
use App\Domain\Lesson\BookingRequest;
use App\Domain\Lesson\CancelReason;
use App\Domain\Lesson\Dto\LessonDto;
use App\Models\Lesson;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\Lesson\EloquentLessonBooker;
use App\Services\Lesson\EloquentLessonReader;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LessonContractsTest extends TestCase
{
    use RefreshDatabase;

    public function test_container_binds_lesson_contracts_to_eloquent_implementations(): void
    {
        $this->assertInstanceOf(EloquentLessonReader::class, app(LessonReader::class));
        $this->assertInstanceOf(EloquentLessonBooker::class, app(LessonBooker::class));
    }

    public function test_reader_find_returns_dto_without_eloquent(): void
    {
        [$tutor, $student] = $this->makeTutorAndStudent();

        $lesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => CarbonImmutable::now('UTC')->addDay(),
            'end_time' => CarbonImmutable::now('UTC')->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        $dto = app(LessonReader::class)->find($lesson->id);

        $this->assertInstanceOf(LessonDto::class, $dto);
        $this->assertSame($lesson->id, $dto->id);
        $this->assertSame($tutor->id, $dto->tutorId);
        $this->assertSame($student->id, $dto->studentId);
        $this->assertSame(60, $dto->durationMinutes);
        $this->assertSame('80.00', $dto->price);
        $this->assertSame(Lesson::STATUS_PENDING, $dto->status);
        $this->assertSame('single', $dto->packageCode);
    }

    public function test_reader_find_returns_null_for_missing_lesson(): void
    {
        $this->assertNull(app(LessonReader::class)->find(999999));
    }

    public function test_reader_for_user_returns_lessons_for_tutor_student_and_parent(): void
    {
        [$tutor, $student] = $this->makeTutorAndStudent();
        $parent = User::factory()->create(['role' => 'parent', 'phone' => '+375298300003']);

        $own = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'start_time' => CarbonImmutable::now('UTC')->addDay(),
            'end_time' => CarbonImmutable::now('UTC')->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        $other = User::factory()->create(['role' => 'student', 'phone' => '+375298300004']);
        Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $other->id,
            'start_time' => CarbonImmutable::now('UTC')->addDays(2),
            'end_time' => CarbonImmutable::now('UTC')->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        $forParent = app(LessonReader::class)->forUser($parent->id);

        $this->assertCount(1, $forParent);
        $this->assertSame($own->id, $forParent[0]->id);
        $this->assertContainsOnlyInstancesOf(LessonDto::class, $forParent);
    }

    public function test_booker_book_delegates_to_booking_service_and_returns_dto(): void
    {
        Notification::fake();

        [$tutor, $student, $profile, $slot] = $this->makeBookableTutor();

        $dto = app(LessonBooker::class)->book(BookingRequest::single(
            tutorProfileId: $profile->id,
            bookerUserId: $student->id,
            startTimeLocal: $slot->format('Y-m-d H:i'),
        ));

        $this->assertInstanceOf(LessonDto::class, $dto);
        $this->assertSame($tutor->id, $dto->tutorId);
        $this->assertSame($student->id, $dto->studentId);
        $this->assertSame(Lesson::STATUS_PENDING, $dto->status);
        $this->assertDatabaseHas('lessons', [
            'id' => $dto->id,
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_booker_cancel_unpaid_lesson_sets_status_cancelled(): void
    {
        Notification::fake();

        [$tutor, $student] = $this->makeTutorAndStudent();

        $lesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => CarbonImmutable::now('UTC')->addDays(3),
            'end_time' => CarbonImmutable::now('UTC')->addDays(3)->addHour(),
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '12.00',
            'net_amount' => '68.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        app(LessonBooker::class)->cancel($lesson->id, CancelReason::lessonCancelled());

        $this->assertSame(Lesson::STATUS_CANCELLED, $lesson->fresh()->status);
    }

    public function test_booker_cancel_missing_lesson_throws(): void
    {
        $this->expectException(ModelNotFoundException::class);

        app(LessonBooker::class)->cancel(999999, CancelReason::lessonCancelled());
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function makeTutorAndStudent(): array
    {
        $tutor = User::factory()->create(['role' => 'tutor', 'phone' => '+375298300001']);
        $student = User::factory()->create(['role' => 'student', 'phone' => '+375298300002']);

        return [$tutor, $student];
    }

    /**
     * @return array{0: User, 1: User, 2: TutorProfile, 3: CarbonImmutable}
     */
    private function makeBookableTutor(): array
    {
        [$tutor, $student] = $this->makeTutorAndStudent();

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

        return [$tutor, $student, $profile, $slot];
    }
}
