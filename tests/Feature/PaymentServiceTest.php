<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\StudentGoal;
use App\Models\Transaction;
use App\Models\TutorBalance;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_payment_creates_transaction_and_updates_balance(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375291111111',
        ]);
        $tutor->tutorProfile()->create([
            'subjects' => ['Белорусский язык'],
            'audiences' => ['Подготовка к ЦЭ'],
            'price_per_hour' => '40.00',
            'experience_years' => 5,
            'legal_status' => 'self_employed',
            'bio' => 'Подготовка к экзаменам.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'lesson_formats' => ['individual_online'],
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375292222222',
        ]);

        $lesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => CarbonImmutable::now('UTC')->addDay(),
            'end_time' => CarbonImmutable::now('UTC')->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'platform_commission' => '6.00',
            'net_amount' => '34.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => CarbonImmutable::now('UTC')->addMinutes(15),
        ]);

        $transaction = app(PaymentService::class)->processPayment($lesson->id, $student->id);

        $this->assertSame(Transaction::STATUS_SUCCESS, $transaction->status);
        $this->assertSame('40.00', $transaction->amount);
        $this->assertSame('6.00', $transaction->platform_commission);
        $this->assertSame('1.18', $transaction->acquiring_fee);
        $this->assertSame('32.82', $transaction->net_amount);

        $lesson->refresh();

        $this->assertSame(Lesson::PAYMENT_PAID, $lesson->payment_status);
        $this->assertSame(Lesson::STATUS_CONFIRMED, $lesson->status);
        $this->assertNull($lesson->payment_lock_expires_at);
        $this->assertDatabaseHas('conversations', [
            'lesson_id' => $lesson->id,
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
        ]);
        $this->assertDatabaseHas('messages', [
            'sender_id' => null,
            'is_system' => true,
            'message' => '🎉 Занятие оплачено! Контакты открыты. Успешного урока!',
        ]);
        $this->assertDatabaseHas('student_goals', [
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'subject' => 'Белорусский язык',
            'exam_type' => 'ЦЭ',
            'status' => 'active',
        ]);

        $goal = StudentGoal::query()->firstOrFail();
        $this->assertDatabaseHas('exam_tracks', [
            'student_goal_id' => $goal->id,
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'status' => 'active',
        ]);

        $balance = TutorBalance::query()->where('user_id', $tutor->id)->first();

        $this->assertNotNull($balance);
        $this->assertSame('32.82', $balance->pending_amount);
        $this->assertSame('0.00', $balance->available_amount);
    }

    public function test_settle_completed_lesson_moves_money_to_available_balance(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375293333333',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375294444444',
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
            'payment_lock_expires_at' => null,
        ]);

        Transaction::query()->create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'amount' => '40.00',
            'platform_commission' => '6.00',
            'acquiring_fee' => '1.18',
            'net_amount' => '32.82',
            'currency' => 'BYN',
            'status' => Transaction::STATUS_SUCCESS,
            'payment_method' => 'card',
            'gateway_transaction_id' => 'mock-1',
            'gateway_response' => [],
            'paid_at' => now('UTC'),
        ]);

        TutorBalance::query()->create([
            'user_id' => $tutor->id,
            'available_amount' => '0.00',
            'pending_amount' => '32.82',
            'total_earned' => '0.00',
            'total_withdrawn' => '0.00',
        ]);

        app(PaymentService::class)->settleCompletedLesson($lesson);

        $balance = TutorBalance::query()->where('user_id', $tutor->id)->firstOrFail();

        $this->assertSame('0.00', $balance->pending_amount);
        $this->assertSame('32.82', $balance->available_amount);
        $this->assertSame('32.82', $balance->total_earned);
    }

    public function test_process_payment_uses_package_total_when_package_selected(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375295555555',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375296666666',
        ]);

        $lesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => CarbonImmutable::now('UTC')->addDay(),
            'end_time' => CarbonImmutable::now('UTC')->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'pack_4',
            'package_lessons' => 4,
            'package_total' => '152.00',
            'package_discount' => '8.00',
            'platform_commission' => '6.00',
            'net_amount' => '34.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => CarbonImmutable::now('UTC')->addMinutes(15),
        ]);

        $transaction = app(PaymentService::class)->processPayment($lesson->id, $student->id, 'card', true);

        $this->assertSame('152.00', $transaction->amount);
        $this->assertSame('22.80', $transaction->platform_commission);
        $this->assertSame('3.64', $transaction->acquiring_fee);
        $this->assertSame('125.56', $transaction->net_amount);
        $this->assertTrue(($transaction->gateway_response['remember_payment_method'] ?? false) === true);
        $this->assertSame('pack_4', $transaction->gateway_response['package_code'] ?? null);
        $this->assertSame(4, $transaction->gateway_response['package_lessons'] ?? null);
    }

    public function test_process_payment_confirms_package_child_lessons(): void
    {
        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375296777771',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+375296777772',
        ]);

        $parentLesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => CarbonImmutable::now('UTC')->addDays(2),
            'end_time' => CarbonImmutable::now('UTC')->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'pack_4',
            'package_lessons' => 4,
            'package_lessons_remaining' => 4,
            'package_total' => '152.00',
            'package_discount' => '8.00',
            'platform_commission' => '22.80',
            'net_amount' => '129.20',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => CarbonImmutable::now('UTC')->addMinutes(15),
        ]);

        $childLesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => CarbonImmutable::now('UTC')->addDays(3),
            'end_time' => CarbonImmutable::now('UTC')->addDays(3)->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'pack_4',
            'package_lessons' => 1,
            'package_parent_lesson_id' => $parentLesson->id,
            'package_discount' => '0.00',
            'platform_commission' => '0.00',
            'net_amount' => '0.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => CarbonImmutable::now('UTC')->addMinutes(15),
        ]);

        app(PaymentService::class)->processPayment($parentLesson->id, $student->id, 'card');

        $this->assertDatabaseHas('lessons', [
            'id' => $childLesson->id,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'payment_lock_expires_at' => null,
        ]);
    }
}
