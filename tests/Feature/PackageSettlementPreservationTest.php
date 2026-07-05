<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\StudentBalance;
use App\Models\Transaction;
use App\Models\TutorBalance;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PackageSettlementPreservationTest extends TestCase
{
    use RefreshDatabase;

    private function bootstrapPaidSingle(bool $useWalletBalance = false, string $walletAmount = '40.00'): array
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375293000001',
        ]);

        $tutor->tutorProfile()->create([
            'subjects' => ['Математика'],
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
            'phone' => '+375293000002',
        ]);

        if ($useWalletBalance) {
            StudentBalance::query()->create([
                'user_id' => $student->id,
                'available_amount' => $walletAmount,
                'locked_amount' => '0.00',
                'total_topped_up' => $walletAmount,
                'total_spent' => '0.00',
                'total_refunded' => '0.00',
            ]);
        }

        $now = CarbonImmutable::now('UTC');

        $lesson = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->addDays(2),
            'end_time' => $now->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'single',
            'package_lessons' => 1,
            'platform_commission' => '4.00',
            'net_amount' => '34.82',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => $now->addMinutes(15),
        ]);

        $transaction = app(PaymentService::class)->processPayment(
            lessonId: $lesson->id,
            userId: $student->id,
            paymentMethod: 'card',
            rememberPaymentMethod: false,
            useWalletBalance: $useWalletBalance,
        );

        return [
            'tutor' => $tutor,
            'student' => $student,
            'lesson' => $lesson->refresh(),
            'transaction' => $transaction,
        ];
    }

    public function test_single_lesson_settle_unchanged(): void
    {
        $ctx = $this->bootstrapPaidSingle();
        /** @var Lesson $lesson */
        $lesson = $ctx['lesson'];
        $lesson->update(['status' => Lesson::STATUS_COMPLETED]);

        app(PaymentService::class)->settleCompletedLesson($lesson->fresh());

        $balance = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        $tx = $ctx['transaction'];

        // Settle single lesson credits tutor with the full transaction.net_amount
        $this->assertSame((string) $tx->net_amount, (string) $balance->available_amount);
        $this->assertSame('0.00', (string) $balance->pending_amount);
    }

    public function test_single_lesson_refund_unchanged(): void
    {
        $ctx = $this->bootstrapPaidSingle();
        /** @var Lesson $lesson */
        $lesson = $ctx['lesson'];

        app(PaymentService::class)->refundLessonPayment($lesson->fresh(), 'student_cancelled');

        $balance = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        $studentBalance = StudentBalance::query()->where('user_id', $ctx['student']->id)->firstOrFail();

        $this->assertSame('0.00', (string) $balance->pending_amount);
        $this->assertSame('0.00', (string) $balance->available_amount);
        $this->assertSame(Lesson::STATUS_CANCELLED, $lesson->refresh()->status);
        $this->assertSame(Lesson::PAYMENT_REFUNDED, $lesson->refresh()->payment_status);
    }

    public function test_single_lesson_wallet_partial_settle_unchanged(): void
    {
        // Parameterized contributor simulation for single lesson with wallet balance
        $ctx = $this->bootstrapPaidSingle(useWalletBalance: true, walletAmount: '15.00');
        /** @var Lesson $lesson */
        $lesson = $ctx['lesson'];
        $lesson->update(['status' => Lesson::STATUS_COMPLETED]);

        app(PaymentService::class)->settleCompletedLesson($lesson->fresh());

        $balance = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        $tx = $ctx['transaction'];

        $this->assertSame((string) $tx->net_amount, (string) $balance->available_amount);
        $this->assertSame('0.00', (string) $balance->pending_amount);
    }

    public function test_legacy_settled_package_no_double_settle(): void
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375293000003',
        ]);
        $tutor->tutorProfile()->create([
            'subjects' => ['Математика'],
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
            'phone' => '+375293000004',
        ]);

        $now = CarbonImmutable::now('UTC');

        $parent = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->addDays(2),
            'end_time' => $now->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'pack_4',
            'package_lessons' => 4,
            'package_lessons_remaining' => 0, // already settled package
            'package_total' => '152.00',
            'package_discount' => '8.00',
            'platform_commission' => '15.20',
            'net_amount' => '133.16',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $child = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->addDays(3),
            'end_time' => $now->addDays(3)->addHour(),
            'duration_minutes' => 60,
            'price' => '40.00',
            'package_code' => 'pack_4',
            'package_lessons' => 1,
            'package_parent_lesson_id' => $parent->id,
            'package_discount' => '0.00',
            'platform_commission' => '0.00',
            'net_amount' => '0.00',
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
        ]);

        $transaction = Transaction::query()->create([
            'lesson_id' => $parent->id,
            'user_id' => $student->id,
            'amount' => '152.00',
            'platform_commission' => '15.20',
            'acquiring_fee' => '3.64',
            'net_amount' => '133.16',
            'currency' => 'BYN',
            'status' => Transaction::STATUS_SUCCESS,
            'payment_method' => 'card',
            'gateway_transaction_id' => 'legacy-tx-123',
            'gateway_response' => [
                'settled' => true, // legacy fully settled flag
                'settled_at' => $now->toIso8601String(),
                'package_code' => 'pack_4',
                'package_lessons' => 4,
            ],
            'paid_at' => $now,
        ]);

        // Manually create TutorBalance in fully settled state to replicate historical DB
        $tutorBalance = TutorBalance::query()->create([
            'user_id' => $tutor->id,
            'available_amount' => '133.16', // already has full amount
            'pending_amount' => '0.00',
            'total_earned' => '133.16',
            'total_withdrawn' => '0.00',
        ]);

        // Run the backfill migration logic manually or ensure that it creates audit row on migration.
        // For the test, we just check that settleCompletedLesson on the child is a no-op
        // and available_amount doesn't change from 133.16.
        app(PaymentService::class)->settleCompletedLesson($child->fresh());

        $tutorBalance->refresh();
        $this->assertSame('133.16', (string) $tutorBalance->available_amount);
        $this->assertSame('0.00', (string) $tutorBalance->pending_amount);
    }
}
