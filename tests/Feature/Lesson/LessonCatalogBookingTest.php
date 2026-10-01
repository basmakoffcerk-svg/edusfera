<?php

declare(strict_types=1);

namespace Tests\Feature\Lesson;

use App\Models\Lesson;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use App\Notifications\LessonBookedStudentNotification;
use App\Notifications\LessonBookedTutorNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LessonCatalogBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $tutor;

    private TutorProfile $profile;

    private User $student;

    private CarbonImmutable $bookingSlot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Анна Преподаватель',
            'email' => 'tutor@edusfera.by',
            'phone' => '+375291112233',
        ]);

        $this->profile = TutorProfile::query()->create([
            'user_id' => $this->tutor->id,
            'subjects' => ['Математика'],
            'price_per_hour' => 80.00,
            'experience_years' => 5,
            'legal_status' => 'npd',
            'bio' => 'Готовлю к ЦТ на высокий балл.',
            'is_verified' => true,
            'rating_avg' => 4.9,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Дмитрий Ученик',
            'email' => 'student@edusfera.by',
            'phone' => '+375294445566',
        ]);

        $this->bookingSlot = CarbonImmutable::now(config('booking.display_timezone', 'Europe/Minsk'))
            ->addDays(2)
            ->setTime(14, 0);

        TutorAvailability::query()->create([
            'user_id' => $this->tutor->id,
            'day_of_week' => $this->bookingSlot->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '20:00:00',
            'is_active' => true,
        ]);
    }

    public function test_student_can_book_lesson_without_payment_redirect_and_notifies_tutor(): void
    {
        Notification::fake();

        $slotFormatted = $this->bookingSlot->format('Y-m-d H:i');

        $response = $this->actingAs($this->student)->post(route('tutors.book', $this->profile), [
            'slot' => $slotFormatted,
            'name' => 'Дмитрий Ученик',
            'phone' => '+375294445566',
            'notes' => 'Нужно разобрать логарифмы и тригонометрию',
            'package' => 'single',
            'terms' => '1',
        ]);

        // Assert redirect is back to tutor profile with booking_success (NOT checkout.show)
        $response->assertRedirect(route('tutors.show', [
            'tutor' => $this->profile,
            'date' => $this->bookingSlot->format('Y-m-d'),
        ]));
        $response->assertSessionHas('booking_success');

        // Verify lesson in DB
        $lesson = Lesson::query()->first();
        $this->assertNotNull($lesson);
        $this->assertSame($this->tutor->id, $lesson->tutor_id);
        $this->assertSame($this->student->id, $lesson->student_id);
        $this->assertSame(Lesson::STATUS_PENDING, $lesson->status);
        $this->assertSame(Lesson::PAYMENT_UNPAID, $lesson->payment_status);
        $this->assertNull($lesson->payment_lock_expires_at, 'Payment lock must be null so booking does not auto-expire');
        $this->assertSame('80.00', (string) $lesson->price);
        $this->assertSame('Нужно разобрать логарифмы и тригонометрию', $lesson->notes);

        // Verify notification was sent to tutor
        Notification::assertSentTo($this->tutor, LessonBookedTutorNotification::class, function ($notification) use ($lesson) {
            $dbData = $notification->toDatabase($this->tutor);
            $this->assertArrayHasKey('title', $dbData);
            $this->assertArrayHasKey('body', $dbData);
            $this->assertSame('/admin/lesson-requests', $dbData['url']);
            $this->assertSame($lesson->id, $dbData['lesson_id']);

            $mail = $notification->toMail($this->tutor);
            $this->assertStringContainsString('Новая бронь занятия', $mail->subject);
            $this->assertStringContainsString('Дмитрий Ученик', $mail->introLines[0]);
            $this->assertStringContainsString('+375294445566', implode(' ', $mail->introLines));
            $this->assertStringContainsString('Нужно разобрать логарифмы и тригонометрию', implode(' ', $mail->introLines));

            return true;
        });

        // Verify notification was sent to student
        Notification::assertSentTo($this->student, LessonBookedStudentNotification::class, function ($notification) {
            $dbData = $notification->toDatabase($this->student);
            $this->assertArrayHasKey('title', $dbData);
            $this->assertSame('/admin/lessons', $dbData['url']);

            return true;
        });
    }

    public function test_booking_locks_slot_preventing_conflict_for_another_student(): void
    {
        // First student books the slot
        $slotFormatted = $this->bookingSlot->format('Y-m-d H:i');

        $this->actingAs($this->student)->post(route('tutors.book', $this->profile), [
            'slot' => $slotFormatted,
            'name' => 'Дмитрий Ученик',
            'phone' => '+375294445566',
            'package' => 'single',
            'terms' => '1',
        ])->assertRedirect();

        // Second student tries to book the exact same slot
        $secondStudent = User::factory()->create([
            'role' => 'student',
            'phone' => '+375297778899',
        ]);

        $conflictResponse = $this->actingAs($secondStudent)->post(route('tutors.book', $this->profile), [
            'slot' => $slotFormatted,
            'name' => 'Второй Ученик',
            'phone' => '+375297778899',
            'package' => 'single',
            'terms' => '1',
        ]);

        $conflictResponse->assertSessionHasErrors(['slot']);
        $this->assertSame(1, Lesson::query()->count());
    }

    public function test_student_can_book_package_without_payment_redirect(): void
    {
        Notification::fake();

        $slots = [
            $this->bookingSlot->setTime(14, 0)->format('Y-m-d H:i'),
            $this->bookingSlot->setTime(15, 0)->format('Y-m-d H:i'),
            $this->bookingSlot->setTime(16, 0)->format('Y-m-d H:i'),
            $this->bookingSlot->setTime(17, 0)->format('Y-m-d H:i'),
        ];

        $response = $this->actingAs($this->student)->post(route('tutors.book', $this->profile), [
            'slots' => $slots,
            'name' => 'Дмитрий Ученик',
            'phone' => '+375294445566',
            'package' => 'pack_4',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('tutors.show', [
            'tutor' => $this->profile,
            'date' => $this->bookingSlot->format('Y-m-d'),
        ]));
        $response->assertSessionHas('booking_success');

        $lessons = Lesson::query()->get();
        $this->assertCount(4, $lessons);

        foreach ($lessons as $lesson) {
            $this->assertSame(Lesson::STATUS_PENDING, $lesson->status);
            $this->assertSame(Lesson::PAYMENT_UNPAID, $lesson->payment_status);
            $this->assertNull($lesson->payment_lock_expires_at);
        }

        Notification::assertSentTo($this->tutor, LessonBookedTutorNotification::class);
    }
}
