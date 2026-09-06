<?php

declare(strict_types=1);

namespace Tests\Feature\Adversarial;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Enums\UserRole;
use App\Models\ClassroomFile;
use App\Models\ClassroomSession;
use App\Models\Lesson;
use App\Models\TutorAvailability;
use App\Models\TutorBalance;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClassroomAndBookingAdversarialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /**
     * Future Lesson Settlement Prevention via ClassroomController::end.
     *
     * In ClassroomController::end():
     * Ending a classroom session for a future lesson must NOT mark the lesson as completed
     * or settle payout prematurely.
     */
    public function test_future_lesson_cannot_be_settled_prematurely(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $student = User::factory()->create(['role' => UserRole::Student]);

        // Lesson is scheduled 10 days in the FUTURE
        $futureStart = CarbonImmutable::now('UTC')->addDays(10);
        $futureEnd = $futureStart->addHour();

        $lesson = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $futureStart,
            'end_time' => $futureEnd,
            'duration_minutes' => 60,
            'price' => '80.00',
            'platform_commission' => '8.00',
            'net_amount' => '70.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        // Transaction backing the paid lesson
        \App\Models\Transaction::query()->create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'amount' => '80.00',
            'platform_commission' => '8.00',
            'acquiring_fee' => '2.00',
            'net_amount' => '70.00',
            'currency' => 'BYN',
            'status' => \App\Models\Transaction::STATUS_SUCCESS,
            'payment_method' => 'wallet',
            'gateway_response' => [
                'charged_amount' => '80.00',
                'payable_amount' => '80.00',
                'wallet_contribution' => '80.00',
            ],
            'paid_at' => now('UTC'),
        ]);

        // Initial tutor balance in pending
        TutorBalance::query()->create([
            'user_id' => $tutor->id,
            'available_amount' => '0.00',
            'pending_amount' => '70.00',
            'total_earned' => '0.00',
            'total_withdrawn' => '0.00',
        ]);

        $this->actingAs($tutor);

        // Tutor calls POST /classroom/{lesson}/end for a lesson 10 days before its scheduled start
        $response = $this->post("/classroom/{$lesson->id}/end");

        $response->assertRedirect();

        $lesson->refresh();
        $tutorBalance = TutorBalance::query()->where('user_id', $tutor->id)->first();

        // 1. Future lesson is NOT marked completed prematurely
        $this->assertEquals(Lesson::STATUS_CONFIRMED, $lesson->status);

        // 2. Settlement did not execute prematurely; funds remain in pending
        $this->assertEquals('0.00', $tutorBalance->available_amount);
        $this->assertEquals('70.00', $tutorBalance->pending_amount);
    }

    /**
     * Files remain accessible across all sessions of the lesson even after classroom ends.
     */
    public function test_files_remain_accessible_after_classroom_ends(): void
    {
        Storage::fake('local');

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $student = User::factory()->create(['role' => UserRole::Student]);

        $now = CarbonImmutable::now('UTC');
        $lesson = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->subHour(),
            'end_time' => $now,
            'duration_minutes' => 60,
            'price' => '60.00',
            'platform_commission' => '6.00',
            'net_amount' => '54.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        // Create an active session and upload a file
        $session = ClassroomSession::query()->create([
            'lesson_id' => $lesson->id,
            'room_id' => 'room-test-12345',
            'status' => ClassroomSession::STATUS_ACTIVE,
        ]);

        $filePath = "classroom-files/{$session->id}/homework.pdf";
        Storage::disk('local')->put($filePath, 'PDF CONTENT');

        $file = ClassroomFile::query()->create([
            'classroom_session_id' => $session->id,
            'uploaded_by' => $tutor->id,
            'original_name' => 'homework.pdf',
            'path' => $filePath,
            'mime_type' => 'application/pdf',
            'size_bytes' => 11,
        ]);

        // When session is ended by tutor:
        $session->update(['status' => ClassroomSession::STATUS_ENDED]);

        $this->actingAs($student);

        // Student tries to download homework file after lesson finished
        $response = $this->get("/classroom/{$lesson->id}/files/{$file->id}/download");

        $response->assertOk();

        // Also check getFiles list returns the file
        $filesResponse = $this->getJson("/classroom/{$lesson->id}/files");
        $filesResponse->assertOk()
            ->assertJsonCount(1);
    }

    /**
     * Student Cannot Double-Book Conflicting Lessons with Multiple Tutors.
     */
    public function test_student_cannot_double_book_simultaneous_lessons_with_multiple_tutors(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $tutor1 = User::factory()->create(['role' => UserRole::Tutor]);
        $tutor2 = User::factory()->create(['role' => UserRole::Tutor]);

        $profile1 = TutorProfile::query()->create([
            'user_id' => $tutor1->id,
            'price_per_hour' => '40.00',
            'is_verified' => true,
        ]);

        $profile2 = TutorProfile::query()->create([
            'user_id' => $tutor2->id,
            'price_per_hour' => '50.00',
            'is_verified' => true,
        ]);

        $targetSlot = CarbonImmutable::now('Europe/Minsk')->addDays(3)->setTime(14, 0);

        // Setup availability for both tutors at the same time
        TutorAvailability::query()->create([
            'user_id' => $tutor1->id,
            'day_of_week' => $targetSlot->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '20:00:00',
            'is_active' => true,
        ]);

        TutorAvailability::query()->create([
            'user_id' => $tutor2->id,
            'day_of_week' => $targetSlot->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '20:00:00',
            'is_active' => true,
        ]);

        $bookingService = app(BookingService::class);

        // 1. Student books Tutor 1 at 14:00
        $lesson1 = $bookingService->createBooking(
            tutorProfile: $profile1,
            booker: $student,
            startTimeLocal: $targetSlot->format('Y-m-d H:i'),
        );
        $this->assertNotNull($lesson1);

        // 2. Student books Tutor 2 at the EXACT SAME TIME 14:00 -> should throw ValidationException
        $this->expectException(ValidationException::class);

        $bookingService->createBooking(
            tutorProfile: $profile2,
            booker: $student,
            startTimeLocal: $targetSlot->format('Y-m-d H:i'),
        );
    }

    /**
     * Robust unique invoice numbers avoid race conditions and collisions.
     */
    public function test_invoice_numbers_are_unique_and_collision_free(): void
    {
        $tutor1 = User::factory()->create(['role' => UserRole::Tutor]);
        $tutor2 = User::factory()->create(['role' => UserRole::Tutor]);

        $service = app(SubscriptionService::class);

        $sub1 = $service->startTrial($tutor1, SubscriptionPlan::PRO);
        $sub2 = $service->startTrial($tutor2, SubscriptionPlan::PRO);

        $inv1 = $service->createInvoice($sub1);
        $inv2 = $service->createInvoice($sub2);

        $this->assertNotEquals($inv1->invoice_number, $inv2->invoice_number);
        $this->assertStringStartsWith('INV-EDU-', $inv1->invoice_number);
        $this->assertStringStartsWith('INV-EDU-', $inv2->invoice_number);
    }

    /**
     * Missing fields in session during diagnostic finish are handled gracefully without 500 error.
     */
    public function test_diagnostic_finish_without_exam_type_in_session_handles_missing_fields_gracefully(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $this->actingAs($student);

        // Put only subject in session (missing exam_type)
        session(['diagnostic_progress' => ['subject' => 'Математика']]);

        $response = $this->get('/diagnostic/result');

        $response->assertOk();
        $response->assertViewIs('diagnostic.result');

        $this->assertDatabaseHas('student_goals', [
            'student_id' => $student->id,
            'subject' => 'Математика',
            'exam_type' => 'ЦТ',
        ]);
    }
}
