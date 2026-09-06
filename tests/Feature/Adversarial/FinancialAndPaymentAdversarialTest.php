<?php

declare(strict_types=1);

namespace Tests\Feature\Adversarial;

use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\StudentBalance;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\Transaction;
use App\Models\TutorBalance;
use App\Models\User;
use App\Models\WalletTopup;
use App\Services\Finance\StudentBalanceService;
use App\Services\Payment\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialAndPaymentAdversarialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /**
     * FIX 1: Settled / Completed Single Lesson Refund is blocked (ValidationException).
     *
     * Prevents double payout / platform loss where tutor keeps earnings and student
     * gets refunded for an already delivered and settled single lesson.
     */
    public function test_completed_single_lesson_refund_is_rejected_preventing_double_payout(): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $student = User::factory()->create(['role' => UserRole::Student]);

        $now = CarbonImmutable::now('UTC');

        $lesson = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->subHours(2),
            'end_time' => $now->subHour(),
            'duration_minutes' => 60,
            'price' => '100.00',
            'platform_commission' => '10.00',
            'net_amount' => '87.50',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'package_code' => 'single',
            'package_lessons' => 1,
        ]);

        $transaction = Transaction::query()->create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'amount' => '100.00',
            'platform_commission' => '10.00',
            'acquiring_fee' => '2.50',
            'net_amount' => '87.50',
            'currency' => 'BYN',
            'status' => Transaction::STATUS_SUCCESS,
            'payment_method' => 'wallet',
            'gateway_response' => [
                'charged_amount' => '100.00',
                'payable_amount' => '100.00',
                'wallet_contribution' => '100.00',
            ],
            'paid_at' => $now,
        ]);

        // Settle the lesson as completed:
        $lesson->update(['status' => Lesson::STATUS_COMPLETED]);
        app(PaymentService::class)->settleCompletedLesson($lesson);

        // Check tutor balance after legitimate settlement:
        $tutorBalance = TutorBalance::query()->where('user_id', $tutor->id)->first();
        $this->assertEquals('87.50', $tutorBalance->available_amount);
        $this->assertEquals('0.00', $tutorBalance->pending_amount);

        // Check student balance: available is 0
        $studentBalance = StudentBalance::query()->where('user_id', $student->id)->first();
        $this->assertEquals('0.00', $studentBalance->available_amount);

        // ATTACK ATTEMPT: Trigger refund on completed and settled lesson
        // MUST throw ValidationException:
        try {
            app(PaymentService::class)->refundLessonPayment($lesson, 'adversarial_double_refund');
            $this->fail('Expected ValidationException was not thrown when attempting to refund settled lesson.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Урок уже проведён, возврат недоступен', $e->getMessage());
        }

        $lesson->refresh();
        $tutorBalance->refresh();
        $studentBalance->refresh();

        // ASSERTIONS:
        // 1. Tutor retains legitimate earnings
        $this->assertEquals('87.50', $tutorBalance->available_amount);
        // 2. Student balance is NOT inflated
        $this->assertEquals('0.00', $studentBalance->available_amount);
        // 3. Lesson status remains completed & paid
        $this->assertEquals(Lesson::STATUS_COMPLETED, $lesson->status);
        $this->assertEquals(Lesson::PAYMENT_PAID, $lesson->payment_status);
    }

    /**
     * FIX 2: Hold release cannot inflate balance beyond actual locked_amount.
     *
     * In StudentBalanceService::releaseHeldForLesson, releasing with 0.00 locked_amount
     * does not increase available_amount from thin air.
     */
    public function test_release_held_for_lesson_without_hold_does_not_inflate_available_balance(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $service = app(StudentBalanceService::class);
        $balance = $service->getOrCreate($student->id);

        $this->assertEquals('0.00', $balance->available_amount);
        $this->assertEquals('0.00', $balance->locked_amount);

        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $lesson = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'duration_minutes' => 60,
            'price' => '50.00',
            'platform_commission' => '5.00',
            'net_amount' => '45.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
        ]);

        // Calling releaseHeldForLesson on a balance with 0 locked_amount
        $service->releaseHeldForLesson(
            balance: $balance,
            amount: '50.00',
            currency: 'BYN',
            lesson: $lesson,
        );

        $balance->refresh();

        // ASSERTION: Available balance remains 0.00 and was NOT inflated out of thin air
        $this->assertEquals('0.00', $balance->available_amount);
        $this->assertEquals('0.00', $balance->locked_amount);
    }

    /**
     * FIX 3: Webhook signature enforcement prevents unsigned and forged webhook execution.
     *
     * In WebPayWebhookController:
     * Unsigned and forged webhooks are rejected with HTTP 403 Forbidden.
     */
    public function test_unsigned_webpay_webhook_is_rejected_and_does_not_topup_wallet(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        // Create pending wallet topup
        $topup = WalletTopup::query()->create([
            'user_id' => $student->id,
            'amount' => '150.00',
            'currency' => 'BYN',
            'status' => 'pending',
            'gateway' => 'webpay',
            'gateway_transaction_id' => 'WEBPAY_FAKE_TX_99999',
        ]);

        Config::set('payments.webhook_require_signature', true);

        // Malicious actor posts unsigned webhook notification
        $response = $this->postJson('/payments/webpay/webhook', [
            'payment_type' => 'completion',
            'transaction_id' => 'WEBPAY_FAKE_TX_99999',
            'status' => 'completed',
        ]);

        $response->assertStatus(403);

        $topup->refresh();
        $balance = StudentBalance::query()->where('user_id', $student->id)->first();

        // ASSERTIONS: Topup remains pending and balance was NOT credited
        $this->assertEquals('pending', $topup->status);
        $this->assertTrue($balance === null || $balance->available_amount === '0.00');
    }

    /**
     * FIX 3.2: Invalid signature is rejected even when signature header/field is passed.
     */
    public function test_forged_webpay_webhook_is_rejected(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);

        $topup = WalletTopup::query()->create([
            'user_id' => $student->id,
            'amount' => '150.00',
            'currency' => 'BYN',
            'status' => 'pending',
            'gateway' => 'webpay',
            'gateway_transaction_id' => 'WEBPAY_FORGED_TX_88888',
        ]);

        Config::set('payments.webpay.secret_key', 'real_secret_key');
        Config::set('payments.webhook_require_signature', true);

        // Post with wrong signature
        $response = $this->postJson('/payments/webpay/webhook', [
            'payment_type' => 'completion',
            'transaction_id' => 'WEBPAY_FORGED_TX_88888',
            'status' => 'completed',
            'ws_signature' => 'invalid_forged_md5_hash',
        ]);

        $response->assertStatus(403);
        $topup->refresh();
        $this->assertEquals('pending', $topup->status);
    }
}

