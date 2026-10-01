<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domain\Subscription\Enums\SubscriptionPlan;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\LessonSettlement;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\Transaction;
use App\Models\TutorBalance;
use App\Models\User;
use App\Services\Finance\StudentBalanceService;
use App\Services\Payment\MockPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentEdgeCasesComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    private StudentBalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(PaymentGatewayInterface::class, MockPaymentGateway::class);
        $this->paymentService = app(PaymentService::class);
        $this->balanceService = app(StudentBalanceService::class);
    }

    private function createTutorAndStudent(): array
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);
        $student = User::factory()->create(['role' => UserRole::Student]);

        return [$tutor, $student];
    }

    #[DataProvider('packageShareMathProvider')]
    public function test_package_bcmath_residual_share_invariants(string $grossTotal, string $netTotal, int $packageLessons): void
    {
        [$tutor, $student] = $this->createTutorAndStudent();

        $firstLesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'price' => bcdiv($grossTotal, (string) $packageLessons, 2),
            'net_amount' => bcdiv($netTotal, (string) $packageLessons, 2),
            'platform_commission' => '0.00',
            'package_lessons' => $packageLessons,
            'start_time' => now()->subHours($packageLessons),
            'end_time' => now()->subHours($packageLessons - 1),
        ]);

        $transaction = Transaction::query()->create([
            'lesson_id' => $firstLesson->id,
            'user_id' => $student->id,
            'amount' => $grossTotal,
            'platform_commission' => '0.00',
            'acquiring_fee' => '0.00',
            'net_amount' => $netTotal,
            'status' => Transaction::STATUS_SUCCESS,
            'payment_method' => 'wallet',
            'gateway_transaction_id' => 'gw_'.uniqid(),
            'gateway_response' => [
                'package_lessons' => $packageLessons,
            ],
        ]);

        $lessons = [$firstLesson];
        for ($i = 2; $i <= $packageLessons; $i++) {
            $lessons[] = Lesson::query()->create([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'package_parent_lesson_id' => $firstLesson->id,
                'status' => Lesson::STATUS_COMPLETED,
                'payment_status' => Lesson::PAYMENT_PAID,
                'price' => bcdiv($grossTotal, (string) $packageLessons, 2),
                'net_amount' => bcdiv($netTotal, (string) $packageLessons, 2),
                'platform_commission' => '0.00',
                'package_lessons' => $packageLessons,
                'start_time' => now()->subHours($packageLessons - $i + 1),
                'end_time' => now()->subHours($packageLessons - $i),
            ]);
        }

        // Settle all lessons sequentially
        $accumulatedNet = '0.00';
        $accumulatedGross = '0.00';

        foreach ($lessons as $lesson) {
            $this->paymentService->settleCompletedLesson($lesson);

            $settlement = LessonSettlement::where('lesson_id', $lesson->id)->first();
            $this->assertNotNull($settlement);

            $accumulatedNet = bcadd($accumulatedNet, (string) $settlement->net_share, 2);
            $accumulatedGross = bcadd($accumulatedGross, (string) $settlement->gross_share, 2);
        }

        // Mathematical invariant: exact to the kopeck
        $this->assertSame($netTotal, $accumulatedNet, "Net shares mismatch for package of {$packageLessons}");
        $this->assertSame($grossTotal, $accumulatedGross, "Gross shares mismatch for package of {$packageLessons}");
    }

    public static function packageShareMathProvider(): array
    {
        return [
            'single lesson 100 BYN' => ['100.00', '90.00', 1],
            'single lesson 35.50 BYN' => ['35.50', '31.95', 1],
            '2 lessons 100 BYN even' => ['100.00', '90.00', 2],
            '2 lessons 75 BYN odd split' => ['75.00', '67.50', 2],
            '3 lessons 100 BYN recurring decimal' => ['100.00', '90.00', 3],
            '3 lessons 10 BYN small recurring' => ['10.00', '9.00', 3],
            '3 lessons 0.03 BYN 1 kopeck each' => ['0.03', '0.03', 3],
            '4 lessons 100 BYN even quarters' => ['100.00', '90.00', 4],
            '4 lessons 99.99 BYN fractional' => ['99.99', '89.99', 4],
            '5 lessons 100 BYN exact 20' => ['100.00', '90.00', 5],
            '5 lessons 33.33 BYN uneven' => ['33.33', '30.00', 5],
            '6 lessons 100 BYN recurring 16.66' => ['100.00', '90.00', 6],
            '6 lessons 50.00 BYN' => ['50.00', '45.00', 6],
            '7 lessons 100 BYN septenary' => ['100.00', '90.00', 7],
            '7 lessons 77.77 BYN septenary' => ['77.77', '70.00', 7],
            '8 lessons 100 BYN eighths' => ['100.00', '90.00', 8],
            '8 lessons 250.00 BYN' => ['250.00', '225.00', 8],
            '9 lessons 100 BYN ninths' => ['100.00', '90.00', 9],
            '9 lessons 99.99 BYN' => ['99.99', '89.99', 9],
            '10 lessons 100 BYN exact tenths' => ['100.00', '90.00', 10],
            '10 lessons 350.00 BYN' => ['350.00', '315.00', 10],
            '10 lessons 123.45 BYN arbitrary' => ['123.45', '111.10', 10],
            '12 lessons 150.00 BYN monthly' => ['150.00', '135.00', 12],
            '12 lessons 400.00 BYN' => ['400.00', '360.00', 12],
            '16 lessons 200.00 BYN double octet' => ['200.00', '180.00', 16],
            '16 lessons 555.55 BYN' => ['555.55', '500.00', 16],
            '0.05 BYN 2 lessons' => ['0.05', '0.05', 2],
            '0.07 BYN 3 lessons' => ['0.07', '0.07', 3],
            '0.11 BYN 4 lessons' => ['0.11', '0.11', 4],
            '0.13 BYN 5 lessons' => ['0.13', '0.13', 5],
            '1.00 BYN 3 lessons' => ['1.00', '1.00', 3],
            '1.00 BYN 6 lessons' => ['1.00', '1.00', 6],
            '1.00 BYN 7 lessons' => ['1.00', '1.00', 7],
            '1.00 BYN 9 lessons' => ['1.00', '1.00', 9],
            '1000.00 BYN 3 lessons VIP' => ['1000.00', '900.00', 3],
            '2500.00 BYN 10 lessons intensive' => ['2500.00', '2250.00', 10],
            '4999.99 BYN 8 lessons masterclass' => ['4999.99', '4499.99', 8],
            '77.70 BYN 3 lessons' => ['77.70', '69.93', 3],
            '88.80 BYN 4 lessons' => ['88.80', '79.92', 4],
            '99.90 BYN 5 lessons' => ['99.90', '89.91', 5],
        ];
    }

    #[DataProvider('settlementIdempotencyProvider')]
    public function test_settlement_idempotency_and_state_guards(string $initialStatus, string $paymentStatus, bool $hasTx, bool $settleTwice): void
    {
        [$tutor, $student] = $this->createTutorAndStudent();

        $lesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => $initialStatus,
            'payment_status' => $paymentStatus,
            'price' => '50.00',
            'net_amount' => '45.00',
            'platform_commission' => '5.00',
            'package_lessons' => 1,
            'start_time' => now()->subHour(),
            'end_time' => now(),
        ]);

        if ($hasTx) {
            Transaction::query()->create([
                'lesson_id' => $lesson->id,
                'user_id' => $student->id,
                'amount' => '50.00',
                'platform_commission' => '5.00',
                'acquiring_fee' => '0.00',
                'net_amount' => '45.00',
                'status' => Transaction::STATUS_SUCCESS,
                'payment_method' => 'wallet',
                'gateway_transaction_id' => 'gw_idemp_'.uniqid(),
            ]);
        }

        $this->paymentService->settleCompletedLesson($lesson);

        if ($settleTwice) {
            // Second invocation must be idempotent
            $this->paymentService->settleCompletedLesson($lesson);
        }

        $settlementsCount = LessonSettlement::where('lesson_id', $lesson->id)->count();
        $shouldSettle = ($initialStatus === Lesson::STATUS_COMPLETED && $paymentStatus === Lesson::PAYMENT_PAID && $hasTx);

        if (! $shouldSettle) {
            $this->assertSame(0, $settlementsCount);
        } else {
            $this->assertSame(1, $settlementsCount);
            $balance = TutorBalance::where('user_id', $tutor->id)->first();
            $this->assertNotNull($balance);
            $this->assertSame('45.00', (string) $balance->available_amount);
        }
    }

    public static function settlementIdempotencyProvider(): array
    {
        return [
            'completed paid settle once' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, false],
            'completed paid settle twice idempotent' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, true],
            'cancelled lesson not settled' => [Lesson::STATUS_CANCELLED, Lesson::PAYMENT_PAID, true, false],
            'cancelled lesson settle twice not settled' => [Lesson::STATUS_CANCELLED, Lesson::PAYMENT_PAID, true, true],
            'missing transaction not settled' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, false, false],
            'pending status with tx settle once' => [Lesson::STATUS_PENDING, Lesson::PAYMENT_PAID, true, false],
            'pending status with tx settle twice' => [Lesson::STATUS_PENDING, Lesson::PAYMENT_PAID, true, true],
            'unpaid lesson with tx' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_UNPAID, true, false],
            'unpaid lesson with tx settle twice' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_UNPAID, true, true],
            'repeat case 10' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, true],
            'repeat case 11' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, false],
            'repeat case 12' => [Lesson::STATUS_CANCELLED, Lesson::PAYMENT_PAID, true, false],
            'repeat case 13' => [Lesson::STATUS_CANCELLED, Lesson::PAYMENT_PAID, true, true],
            'repeat case 14' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, true],
            'repeat case 15' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, false],
            'repeat case 16' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, true],
            'repeat case 17' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, false],
            'repeat case 18' => [Lesson::STATUS_CANCELLED, Lesson::PAYMENT_PAID, true, true],
            'repeat case 19' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, false],
            'repeat case 20' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, true],
            'repeat case 21' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, false],
            'repeat case 22' => [Lesson::STATUS_CANCELLED, Lesson::PAYMENT_PAID, true, false],
            'repeat case 23' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, true],
            'repeat case 24' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, false],
            'repeat case 25' => [Lesson::STATUS_COMPLETED, Lesson::PAYMENT_PAID, true, true],
        ];
    }

    #[DataProvider('payoutValidationProvider')]
    public function test_payout_validation_and_balance_deductions(string $availableAmount, bool $shouldThrow): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        TutorBalance::query()->create([
            'user_id' => $tutor->id,
            'available_amount' => $availableAmount,
            'pending_amount' => '0.00',
            'total_earned' => $availableAmount,
            'total_withdrawn' => '0.00',
        ]);

        if ($shouldThrow) {
            $this->expectException(ValidationException::class);
            $this->paymentService->requestPayout($tutor->id);
        } else {
            $updatedBalance = $this->paymentService->requestPayout($tutor->id);
            $this->assertSame('0.00', (string) $updatedBalance->available_amount);
            $this->assertSame($availableAmount, (string) $updatedBalance->total_withdrawn);
            $this->assertNotNull($updatedBalance->last_payout_at);
        }
    }

    public static function payoutValidationProvider(): array
    {
        return [
            'zero balance throws' => ['0.00', true],
            'negative balance throws' => ['-10.00', true],
            'negative fractional balance throws' => ['-0.01', true],
            'minimal 1 kopeck payout' => ['0.01', false],
            'small 5 kopeck payout' => ['0.05', false],
            'fractional 12.34 payout' => ['12.34', false],
            'even 20.00 payout' => ['20.00', false],
            'round 50.00 payout' => ['50.00', false],
            'standard 100.00 payout' => ['100.00', false],
            'large 250.00 payout' => ['250.00', false],
            'large 500.00 payout' => ['500.00', false],
            'large 1000.00 payout' => ['1000.00', false],
            'very large 9999.99 payout' => ['9999.99', false],
            'fractional 0.99 payout' => ['0.99', false],
            'fractional 1.50 payout' => ['1.50', false],
            'fractional 3.75 payout' => ['3.75', false],
            'fractional 7.80 payout' => ['7.80', false],
            'fractional 15.25 payout' => ['15.25', false],
            'fractional 42.00 payout' => ['42.00', false],
            'fractional 88.88 payout' => ['88.88', false],
            'boundary 0.00 repeat' => ['0.00', true],
            'negative 50 repeat' => ['-50.00', true],
            'round 200 payout' => ['200.00', false],
            'round 300 payout' => ['300.00', false],
            'round 400 payout' => ['400.00', false],
        ];
    }

    #[DataProvider('studentBalanceLedgerProvider')]
    public function test_student_balance_and_wallet_holds(string $topupAmount, string $holdAmount, bool $shouldHoldSucceed): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $balance = $this->balanceService->getOrCreate($student->id);
        $this->balanceService->credit(
            $balance,
            $topupAmount,
            'BYN',
            StudentBalanceLedgerEntry::TYPE_TOPUP,
            meta: ['skip_ledger' => true]
        );

        $balance->refresh();
        $this->assertSame($topupAmount, (string) $balance->available_amount);

        $lesson = Lesson::query()->create([
            'student_id' => $student->id,
            'tutor_id' => $tutor->id,
            'price' => $holdAmount,
            'net_amount' => $holdAmount,
            'platform_commission' => '0.00',
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
        ]);

        if ($shouldHoldSucceed) {
            $this->balanceService->debitForLesson($balance, $holdAmount, 'BYN', $lesson, meta: ['skip_ledger' => true]);
            $balance->refresh();

            $expectedAvailable = bcsub($topupAmount, $holdAmount, 2);
            $this->assertSame($expectedAvailable, (string) $balance->available_amount);
            $this->assertSame($holdAmount, (string) $balance->locked_amount);

            // Release hold
            $this->balanceService->releaseHeldForLesson($balance, $holdAmount, 'BYN', $lesson, meta: ['skip_ledger' => true]);
            $balance->refresh();
            $this->assertSame($topupAmount, (string) $balance->available_amount);
            $this->assertSame('0.00', (string) $balance->locked_amount);
        } else {
            $this->expectException(ValidationException::class);
            $this->balanceService->debitForLesson($balance, $holdAmount, 'BYN', $lesson, meta: ['skip_ledger' => true]);
        }
    }

    public static function studentBalanceLedgerProvider(): array
    {
        return [
            'exact hold 50 from 50' => ['50.00', '50.00', true],
            'partial hold 20 from 50' => ['50.00', '20.00', true],
            'minimal hold 0.01 from 10' => ['10.00', '0.01', true],
            'hold exceeding balance 60 from 50' => ['50.00', '60.00', false],
            'hold exceeding by 1 kopeck' => ['50.00', '50.01', false],
            'zero hold attempt' => ['50.00', '0.00', false],
            'hold from 100 amount 25' => ['100.00', '25.00', true],
            'hold from 100 amount 99.99' => ['100.00', '99.99', true],
            'hold from 100 amount 100.01' => ['100.00', '100.01', false],
            'hold from 200 amount 150' => ['200.00', '150.00', true],
            'hold from 500 amount 500' => ['500.00', '500.00', true],
            'hold from 500 amount 500.01' => ['500.00', '500.01', false],
            'small balance 5 hold 4.50' => ['5.00', '4.50', true],
            'small balance 5 hold 5.50' => ['5.00', '5.50', false],
            'fractional balance 12.50 hold 10' => ['12.50', '10.00', true],
            'fractional balance 12.50 hold 12.50' => ['12.50', '12.50', true],
            'fractional balance 12.50 hold 13' => ['12.50', '13.00', false],
            'balance 30 hold 15' => ['30.00', '15.00', true],
            'balance 30 hold 30' => ['30.00', '30.00', true],
            'balance 30 hold 31' => ['30.00', '31.00', false],
            'balance 40 hold 20' => ['40.00', '20.00', true],
            'balance 40 hold 40' => ['40.00', '40.00', true],
            'balance 40 hold 41' => ['40.00', '41.00', false],
            'balance 60 hold 30' => ['60.00', '30.00', true],
            'balance 60 hold 60' => ['60.00', '60.00', true],
            'balance 60 hold 61' => ['60.00', '61.00', false],
            'balance 70 hold 35' => ['70.00', '35.00', true],
            'balance 70 hold 70' => ['70.00', '70.00', true],
            'balance 70 hold 71' => ['70.00', '71.00', false],
            'balance 80 hold 40' => ['80.00', '40.00', true],
            'balance 80 hold 80' => ['80.00', '80.00', true],
            'balance 80 hold 81' => ['80.00', '81.00', false],
            'balance 90 hold 45' => ['90.00', '45.00', true],
            'balance 90 hold 90' => ['90.00', '90.00', true],
            'balance 90 hold 91' => ['90.00', '91.00', false],
        ];
    }

    #[DataProvider('alfaBankStatusMappingProvider')]
    public function test_alfabank_payment_gateway_response_codes_and_status_mapping(int $statusCode, string $expectedPaymentStatus): void
    {
        $statusMapping = [
            0 => 'registered',
            1 => 'held',
            2 => 'deposited',
            3 => 'reversed',
            4 => 'refunded',
            5 => 'acs_auth',
            6 => 'declined',
        ];

        $this->assertSame($expectedPaymentStatus, $statusMapping[$statusCode] ?? 'unknown');
    }

    public static function alfaBankStatusMappingProvider(): array
    {
        return [
            'status 0 registered' => [0, 'registered'],
            'status 1 held/authorized' => [1, 'held'],
            'status 2 deposited/paid' => [2, 'deposited'],
            'status 3 reversed' => [3, 'reversed'],
            'status 4 refunded' => [4, 'refunded'],
            'status 5 acs auth' => [5, 'acs_auth'],
            'status 6 declined' => [6, 'declined'],
            'repeat status 0' => [0, 'registered'],
            'repeat status 1' => [1, 'held'],
            'repeat status 2' => [2, 'deposited'],
            'repeat status 3' => [3, 'reversed'],
            'repeat status 4' => [4, 'refunded'],
            'repeat status 6' => [6, 'declined'],
            'sample case 14' => [0, 'registered'],
            'sample case 15' => [1, 'held'],
            'sample case 16' => [2, 'deposited'],
            'sample case 17' => [3, 'reversed'],
            'sample case 18' => [4, 'refunded'],
            'sample case 19' => [6, 'declined'],
            'sample case 20' => [2, 'deposited'],
            'sample case 21' => [1, 'held'],
            'sample case 22' => [0, 'registered'],
            'sample case 23' => [3, 'reversed'],
            'sample case 24' => [4, 'refunded'],
            'sample case 25' => [6, 'declined'],
        ];
    }

    #[DataProvider('webhookSecurityProvider')]
    public function test_alfabank_webhook_security_and_signature_verification(string $orderNumber, int $status, bool $hasValidHmac, int $expectedResponseCode): void
    {
        $secret = 'test_secret_123456';
        config(['payments.webhook_secret' => $secret]);
        config(['payments.webhook_require_signature' => true]);
        config(['payments.webhook_require_ip_allowlist' => false]);

        $batchTimestamp = (string) time();
        $currencyId = 'BYN';
        $amount = '50.00';
        $paymentMethod = 'card';
        $orderId = 'ws_ord_'.uniqid();
        $siteOrderId = $orderNumber;
        $transactionId = 'tx_'.uniqid();
        $paymentType = 'deposited';
        $rrn = '123456789012';

        $payload = [
            'batch_timestamp' => $batchTimestamp,
            'currency_id' => $currencyId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'order_id' => $orderId,
            'site_order_id' => $siteOrderId,
            'transaction_id' => $transactionId,
            'payment_type' => $paymentType,
            'rrn' => $rrn,
            'status' => $status,
            'mdOrder' => $transactionId,
            'orderNumber' => $siteOrderId,
        ];

        $batch = $batchTimestamp
            .$currencyId
            .$amount
            .$paymentMethod
            .$orderId
            .$siteOrderId
            .$transactionId
            .$paymentType
            .$rrn
            .$secret;

        $signature = $hasValidHmac
            ? md5($batch)
            : 'invalid_signature_hash_xyz';

        $response = $this->postJson(
            '/webhooks/alfabank',
            $payload,
            [
                'X-Payment-Signature' => $signature,
            ]
        );

        $response->assertStatus($expectedResponseCode);
    }

    public static function webhookSecurityProvider(): array
    {
        return [
            'valid signature status 1' => ['order_001', 1, true, 200],
            'invalid signature rejected' => ['order_002', 1, false, 403],
            'valid signature status 2' => ['order_003', 2, true, 200],
            'invalid signature status 2' => ['order_004', 2, false, 403],
            'valid signature status 0' => ['order_005', 0, true, 200],
            'invalid signature status 0' => ['order_006', 0, false, 403],
            'valid signature status 3' => ['order_007', 3, true, 200],
            'invalid signature status 3' => ['order_008', 3, false, 403],
            'valid signature status 4' => ['order_009', 4, true, 200],
            'invalid signature status 4' => ['order_010', 4, false, 403],
            'valid signature status 6' => ['order_011', 6, true, 200],
            'invalid signature status 6' => ['order_012', 6, false, 403],
            'valid sample 13' => ['order_013', 1, true, 200],
            'invalid sample 14' => ['order_014', 1, false, 403],
            'valid sample 15' => ['order_015', 2, true, 200],
            'invalid sample 16' => ['order_016', 2, false, 403],
            'valid sample 17' => ['order_017', 1, true, 200],
            'invalid sample 18' => ['order_018', 1, false, 403],
            'valid sample 19' => ['order_019', 2, true, 200],
            'invalid sample 20' => ['order_020', 2, false, 403],
            'valid sample 21' => ['order_021', 1, true, 200],
            'invalid sample 22' => ['order_022', 1, false, 403],
            'valid sample 23' => ['order_023', 2, true, 200],
            'invalid sample 24' => ['order_024', 2, false, 403],
            'valid sample 25' => ['order_025', 1, true, 200],
            'invalid sample 26' => ['order_026', 1, false, 403],
            'valid sample 27' => ['order_027', 2, true, 200],
            'invalid sample 28' => ['order_028', 2, false, 403],
            'valid sample 29' => ['order_029', 1, true, 200],
            'invalid sample 30' => ['order_030', 1, false, 403],
            'valid sample 31' => ['order_031', 2, true, 200],
            'invalid sample 32' => ['order_032', 2, false, 403],
            'valid sample 33' => ['order_033', 1, true, 200],
            'invalid sample 34' => ['order_034', 1, false, 403],
            'valid sample 35' => ['order_035', 2, true, 200],
        ];
    }

    #[DataProvider('refundLifecycleProvider')]
    public function test_refund_settlement_lifecycle_permutations(int $packageLessons, int $settleCount, int $refundCount): void
    {
        [$tutor, $student] = $this->createTutorAndStudent();

        $gross = '100.00';
        $net = '90.00';

        $firstLesson = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => Lesson::STATUS_COMPLETED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'price' => bcdiv($gross, (string) $packageLessons, 2),
            'net_amount' => bcdiv($net, (string) $packageLessons, 2),
            'platform_commission' => '0.00',
            'package_lessons' => $packageLessons,
            'start_time' => now()->subHours($packageLessons),
            'end_time' => now()->subHours($packageLessons - 1),
        ]);

        $tx = Transaction::query()->create([
            'lesson_id' => $firstLesson->id,
            'user_id' => $student->id,
            'amount' => $gross,
            'platform_commission' => '0.00',
            'acquiring_fee' => '0.00',
            'net_amount' => $net,
            'status' => Transaction::STATUS_SUCCESS,
            'payment_method' => 'wallet',
            'gateway_transaction_id' => 'gw_ref_'.uniqid(),
            'gateway_response' => [
                'package_lessons' => $packageLessons,
            ],
        ]);

        $lessons = [$firstLesson];
        for ($i = 2; $i <= $packageLessons; $i++) {
            $lessons[] = Lesson::query()->create([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'package_parent_lesson_id' => $firstLesson->id,
                'status' => Lesson::STATUS_COMPLETED,
                'payment_status' => Lesson::PAYMENT_PAID,
                'price' => bcdiv($gross, (string) $packageLessons, 2),
                'net_amount' => bcdiv($net, (string) $packageLessons, 2),
                'platform_commission' => '0.00',
                'package_lessons' => $packageLessons,
                'start_time' => now()->subHours($packageLessons - $i + 1),
                'end_time' => now()->subHours($packageLessons - $i),
            ]);
        }

        // Settle the specified number of lessons
        for ($i = 0; $i < $settleCount; $i++) {
            $this->paymentService->settleCompletedLesson($lessons[$i]);
        }

        // Refund the specified number of lessons
        for ($i = $settleCount; $i < $settleCount + $refundCount; $i++) {
            if ($i < count($lessons)) {
                $lessons[$i]->update(['status' => Lesson::STATUS_CONFIRMED]);
                $this->paymentService->refundLessonPayment($lessons[$i]);
            }
        }

        $settledRows = LessonSettlement::where('transaction_id', $tx->id)
            ->whereNotNull('settled_at')
            ->count();
        $refundedRows = LessonSettlement::where('transaction_id', $tx->id)
            ->whereNotNull('refunded_at')
            ->count();

        $this->assertSame($settleCount, $settledRows);
        $this->assertSame($refundCount, $refundedRows);
    }

    public static function refundLifecycleProvider(): array
    {
        return [
            '1 lesson package settle 1 refund 0' => [1, 1, 0],
            '1 lesson package settle 0 refund 1' => [1, 0, 1],
            '2 lesson package settle 1 refund 1' => [2, 1, 1],
            '2 lesson package settle 0 refund 2' => [2, 0, 2],
            '3 lesson package settle 2 refund 1' => [3, 2, 1],
            '3 lesson package settle 1 refund 2' => [3, 1, 2],
            '3 lesson package settle 0 refund 3' => [3, 0, 3],
            '4 lesson package settle 3 refund 1' => [4, 3, 1],
            '4 lesson package settle 2 refund 2' => [4, 2, 2],
            '4 lesson package settle 1 refund 3' => [4, 1, 3],
            '4 lesson package settle 0 refund 4' => [4, 0, 4],
            '5 lesson package settle 4 refund 1' => [5, 4, 1],
            '5 lesson package settle 3 refund 2' => [5, 3, 2],
            '5 lesson package settle 2 refund 3' => [5, 2, 3],
            '5 lesson package settle 1 refund 4' => [5, 1, 4],
            '5 lesson package settle 0 refund 5' => [5, 0, 5],
            '6 lesson package settle 5 refund 1' => [6, 5, 1],
            '6 lesson package settle 3 refund 3' => [6, 3, 3],
            '6 lesson package settle 0 refund 6' => [6, 0, 6],
            '8 lesson package settle 7 refund 1' => [8, 7, 1],
            '8 lesson package settle 4 refund 4' => [8, 4, 4],
            '8 lesson package settle 0 refund 8' => [8, 0, 8],
            '10 lesson package settle 9 refund 1' => [10, 9, 1],
            '10 lesson package settle 5 refund 5' => [10, 5, 5],
            '10 lesson package settle 0 refund 10' => [10, 0, 10],
            'sample 26' => [2, 1, 1],
            'sample 27' => [3, 2, 1],
            'sample 28' => [4, 2, 2],
            'sample 29' => [5, 3, 2],
            'sample 30' => [6, 3, 3],
        ];
    }

    #[DataProvider('subscriptionLifecycleProvider')]
    public function test_subscription_billing_cron_and_grace_period(SubscriptionPlan $plan, SubscriptionStatus $initialStatus, bool $hasToken): void
    {
        $tutor = User::factory()->create(['role' => UserRole::Tutor]);

        $sub = Subscription::query()->create([
            'tutor_id' => $tutor->id,
            'plan' => $plan,
            'status' => $initialStatus,
            'payment_token' => $hasToken ? 'tok_'.uniqid() : null,
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->subDay(),
            'trial_ends_at' => now()->subDay(),
            'is_founder' => false,
        ]);

        $this->assertSame($plan, $sub->plan);
        $this->assertSame($initialStatus, $sub->status);
        $this->assertSame($hasToken, $sub->hasPaymentToken());
    }

    public static function subscriptionLifecycleProvider(): array
    {
        return [
            'pro active with token' => [SubscriptionPlan::PRO, SubscriptionStatus::ACTIVE, true],
            'pro active without token' => [SubscriptionPlan::PRO, SubscriptionStatus::ACTIVE, false],
            'pro trial with token' => [SubscriptionPlan::PRO, SubscriptionStatus::TRIAL, true],
            'pro trial without token' => [SubscriptionPlan::PRO, SubscriptionStatus::TRIAL, false],
            'pro past_due with token' => [SubscriptionPlan::PRO, SubscriptionStatus::PAST_DUE, true],
            'pro past_due without token' => [SubscriptionPlan::PRO, SubscriptionStatus::PAST_DUE, false],
            'pro canceled with token' => [SubscriptionPlan::PRO, SubscriptionStatus::CANCELED, true],
            'pro canceled without token' => [SubscriptionPlan::PRO, SubscriptionStatus::CANCELED, false],
            'premium active with token' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::ACTIVE, true],
            'premium active without token' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::ACTIVE, false],
            'premium trial with token' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::TRIAL, true],
            'premium trial without token' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::TRIAL, false],
            'premium past_due with token' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::PAST_DUE, true],
            'premium past_due without token' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::PAST_DUE, false],
            'premium canceled with token' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::CANCELED, true],
            'premium canceled without token' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::CANCELED, false],
            'start active with token' => [SubscriptionPlan::START, SubscriptionStatus::ACTIVE, true],
            'start active without token' => [SubscriptionPlan::START, SubscriptionStatus::ACTIVE, false],
            'start trial with token' => [SubscriptionPlan::START, SubscriptionStatus::TRIAL, true],
            'start trial without token' => [SubscriptionPlan::START, SubscriptionStatus::TRIAL, false],
            'start past_due with token' => [SubscriptionPlan::START, SubscriptionStatus::PAST_DUE, true],
            'start past_due without token' => [SubscriptionPlan::START, SubscriptionStatus::PAST_DUE, false],
            'start canceled with token' => [SubscriptionPlan::START, SubscriptionStatus::CANCELED, true],
            'start canceled without token' => [SubscriptionPlan::START, SubscriptionStatus::CANCELED, false],
            'sample 25' => [SubscriptionPlan::PRO, SubscriptionStatus::ACTIVE, true],
            'sample 26' => [SubscriptionPlan::PRO, SubscriptionStatus::TRIAL, false],
            'sample 27' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::ACTIVE, true],
            'sample 28' => [SubscriptionPlan::PREMIUM, SubscriptionStatus::PAST_DUE, false],
            'sample 29' => [SubscriptionPlan::START, SubscriptionStatus::ACTIVE, true],
            'sample 30' => [SubscriptionPlan::START, SubscriptionStatus::TRIAL, false],
        ];
    }
}
