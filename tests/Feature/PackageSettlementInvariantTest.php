<?php

declare(strict_types=1);

namespace Tests\Feature;

/*
|--------------------------------------------------------------------------
| Тесты расчетов пакетных уроков
|--------------------------------------------------------------------------
|
| Проверка корректности per-lesson partial settlement и capture для пакетов уроков.
|
*/

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

class PackageSettlementInvariantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build the canonical pack_4 fixture from design.md §Examples.
     *
     * @return array{
     *     tutor: User,
     *     student: User,
     *     parent: Lesson,
     *     children: array<int, Lesson>,
     *     transaction: Transaction,
     * }
     */
    private function bootstrapPaidPack4(bool $useWalletBalance = false, string $walletAmount = '152.00'): array
    {
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+375290000001',
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
            'phone' => '+375290000002',
        ]);

        if ($useWalletBalance) {
            // Pre-fund wallet so processPayment can fully use the wallet path:
            // walletContribution == package_total → chargeAmount == 0 →
            // effectivePaymentMethod becomes 'wallet' → refund will go via
            // releaseHeldForLesson (wallet) instead of through gateway.
            StudentBalance::query()->create([
                'user_id' => $student->id,
                'available_amount' => $walletAmount,
                'locked_amount' => '0.00',
                'total_topped_up' => $walletAmount,
                'total_spent' => '0.00',
                'total_refunded' => '0.00',
            ]);
        }

        // pack_4 with price_per_hour = 40 BYN:
        //   package_total = 40 * 4 * 0.95 = 152.00
        //   discount       = 40 * 4 - 152.00 = 8.00
        //   commission     = 152.00 * 0.15 = 22.80
        //   acquiring_fee  = round(152.00 * 0.022 + 0.30, 2) = 3.64
        //   net_amount     = 152.00 - 22.80 - 3.64 = 125.56

        $now = CarbonImmutable::now('UTC');

        $parent = Lesson::query()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->addDays(2),
            'end_time' => $now->addDays(2)->addHour(),
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
            'payment_lock_expires_at' => $now->addMinutes(15),
        ]);

        $children = [];
        for ($i = 0; $i < 3; $i++) {
            $children[] = Lesson::query()->create([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'start_time' => $now->addDays(3 + $i),
                'end_time' => $now->addDays(3 + $i)->addHour(),
                'duration_minutes' => 60,
                'price' => '40.00',
                'package_code' => 'pack_4',
                'package_lessons' => 1,
                'package_parent_lesson_id' => $parent->id,
                'package_discount' => '0.00',
                'platform_commission' => '0.00',
                'net_amount' => '0.00',
                'status' => Lesson::STATUS_PENDING,
                'payment_status' => Lesson::PAYMENT_UNPAID,
                'payment_lock_expires_at' => $now->addMinutes(15),
            ]);
        }

        $transaction = app(PaymentService::class)->processPayment(
            lessonId: $parent->id,
            userId: $student->id,
            paymentMethod: 'card',
            rememberPaymentMethod: false,
            useWalletBalance: $useWalletBalance,
        );

        // Sanity-check the canonical fixture before each test runs the
        // bug-condition assertion. If processPayment changes pricing math
        // these constants must stay correct, otherwise the counterexample
        // becomes meaningless.
        $this->assertSame('152.00', (string) $transaction->amount);
        $this->assertSame('125.56', (string) $transaction->net_amount);

        return [
            'tutor' => $tutor,
            'student' => $student,
            'parent' => $parent->refresh(),
            'children' => array_map(fn (Lesson $lesson): Lesson => $lesson->refresh(), $children),
            'transaction' => $transaction,
        ];
    }

    /**
     * Property 1 — Bug Condition: settle of the FIRST (parent) package
     * lesson MUST credit only one share, not the entire transaction.net_amount.
     *
     * **Validates: Requirement 1.1**
     *
     * Expected base net share: floor(125.56 / 4, 2) = 31.39
     * On UNFIXED code: full 125.56 lands in available_amount → FAIL.
     */
    public function test_first_package_settle_should_only_credit_one_share(): void
    {
        $ctx = $this->bootstrapPaidPack4();

        /** @var Lesson $parent */
        $parent = $ctx['parent'];

        // Mark the parent as completed (CompleteLessonsCommand would do this
        // in production based on end_time being in the past).
        $parent->update(['status' => Lesson::STATUS_COMPLETED]);

        app(PaymentService::class)->settleCompletedLesson($parent->fresh());

        $balance = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();

        $this->assertSame(
            '31.39',
            (string) $balance->available_amount,
            'Settle of the first package lesson must credit ONLY floor(net_amount / package_lessons, 2) = 31.39 BYN, '
            .'not the full transaction.net_amount (125.56). On UNFIXED code available_amount == 125.56 → bug confirmed.'
        );

        $this->assertSame(
            '94.17', // 125.56 - 31.39 = 94.17 (3 shares still pending)
            (string) $balance->pending_amount,
            'Pending must retain (N-1) shares = 94.17 BYN after first settle. '
            .'On UNFIXED code pending_amount == 0.00.'
        );
    }

    /**
     * Property 1 — Bug Condition: settle of a CHILD package lesson must
     * credit one share (resolved via package_parent_lesson_id), not no-op.
     *
     * **Validates: Requirement 1.2**
     *
     * On UNFIXED code: child has no own Transaction (UNIQUE transactions.lesson_id
     * binds it to the parent), so `! $lesson->transaction` is true and
     * settleCompletedLesson exits silently → balance delta = 0 → FAIL.
     */
    public function test_child_package_lesson_settle_credits_share(): void
    {
        $ctx = $this->bootstrapPaidPack4();

        /** @var Lesson $child */
        $child = $ctx['children'][0];

        $balanceBefore = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        $availableBefore = (string) $balanceBefore->available_amount;
        $pendingBefore = (string) $balanceBefore->pending_amount;

        $child->update(['status' => Lesson::STATUS_COMPLETED]);

        app(PaymentService::class)->settleCompletedLesson($child->fresh());

        $balanceAfter = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();

        $availableDelta = bcsub((string) $balanceAfter->available_amount, $availableBefore, 2);
        $pendingDelta = bcsub((string) $balanceAfter->pending_amount, $pendingBefore, 2);

        $this->assertSame(
            '31.39',
            $availableDelta,
            'Settle of a child package lesson must credit floor(125.56 / 4, 2) = 31.39 BYN. '
            .'On UNFIXED code child has no own Transaction → settle is a no-op → delta = 0.00 → bug confirmed.'
        );

        $this->assertSame(
            '-31.39',
            $pendingDelta,
            'Pending must decrease by exactly one share (31.39 BYN) on a child settle. '
            .'On UNFIXED code pending stays unchanged.'
        );
    }

    /**
     * Property 2 — Bug Condition: refund of an UNSETTLED child package lesson
     * must decrement tutor.pending by one net share AND return one gross
     * share to the student wallet.
     *
     * **Validates: Requirements 1.4, 1.5**
     *
     * Expected: pending −= 31.39; gross 38.00 returned to wallet.
     * On UNFIXED code: child has no transaction, refundLessonPayment exits
     * after only setting lesson.status = cancelled — no financial movement → FAIL.
     */
    public function test_refund_unsettled_child_decrements_pending_by_share(): void
    {
        // useWalletBalance=true so refund goes through the wallet path
        // (releaseHeldForLesson) and we can assert the wallet credit directly.
        $ctx = $this->bootstrapPaidPack4(useWalletBalance: true);

        /** @var Lesson $child */
        $child = $ctx['children'][1];

        $tutorBalanceBefore = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        $studentBalanceBefore = StudentBalance::query()->where('user_id', $ctx['student']->id)->firstOrFail();

        $pendingBefore = (string) $tutorBalanceBefore->pending_amount;
        $availableBefore = (string) $tutorBalanceBefore->available_amount;
        $studentAvailableBefore = (string) $studentBalanceBefore->available_amount;
        $studentLockedBefore = (string) $studentBalanceBefore->locked_amount;

        app(PaymentService::class)->refundLessonPayment($child->fresh(), 'student_cancelled');

        $tutorBalanceAfter = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        $studentBalanceAfter = StudentBalance::query()->where('user_id', $ctx['student']->id)->firstOrFail();

        $pendingDelta = bcsub((string) $tutorBalanceAfter->pending_amount, $pendingBefore, 2);
        $availableDelta = bcsub((string) $tutorBalanceAfter->available_amount, $availableBefore, 2);
        $studentAvailableDelta = bcsub((string) $studentBalanceAfter->available_amount, $studentAvailableBefore, 2);
        $studentLockedDelta = bcsub((string) $studentBalanceAfter->locked_amount, $studentLockedBefore, 2);

        $this->assertSame(
            '-31.39',
            $pendingDelta,
            'Refund of an unsettled child package lesson must reduce tutor.pending_amount by exactly one '
            .'net share (31.39 BYN). On UNFIXED code child has no transaction → refund early-returns after '
            .'setting status = cancelled → pending delta = 0.00 → bug confirmed.'
        );

        $this->assertSame(
            '0.00',
            $availableDelta,
            'available_amount must NOT change when refunding an unsettled lesson — settled-доля сохраняется.'
        );

        $this->assertSame(
            '38.00',
            $studentAvailableDelta,
            'Student wallet must be credited with floor(152.00 / 4, 2) = 38.00 BYN gross share. '
            .'On UNFIXED code no funds are returned to the student.'
        );

        $this->assertSame(
            '-38.00',
            $studentLockedDelta,
            'Student locked_amount must decrease by exactly one gross share (38.00 BYN). '
            .'On UNFIXED code locked stays unchanged.'
        );

        $child->refresh();
        $this->assertSame(Lesson::STATUS_CANCELLED, $child->status);
        $this->assertSame(Lesson::PAYMENT_REFUNDED, $child->payment_status);
    }

    /**
     * Test that refund of a partially wallet-funded package lesson
     * successfully credits the student's wallet rather than attempting a card gateway refund.
     */
    public function test_refund_partial_wallet_payment_credits_wallet(): void
    {
        // 100.00 BYN from wallet, 52.00 BYN from card (total 152.00 BYN)
        $ctx = $this->bootstrapPaidPack4(useWalletBalance: true, walletAmount: '100.00');

        /** @var Lesson $child */
        $child = $ctx['children'][0];

        $tutorBalanceBefore = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        $studentBalanceBefore = StudentBalance::query()->where('user_id', $ctx['student']->id)->firstOrFail();

        $pendingBefore = (string) $tutorBalanceBefore->pending_amount;
        $studentAvailableBefore = (string) $studentBalanceBefore->available_amount;
        $studentLockedBefore = (string) $studentBalanceBefore->locked_amount;

        // Refund one child lesson (gross share = 38.00)
        app(PaymentService::class)->refundLessonPayment($child->fresh(), 'student_cancelled');

        $tutorBalanceAfter = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
        $studentBalanceAfter = StudentBalance::query()->where('user_id', $ctx['student']->id)->firstOrFail();

        $pendingDelta = bcsub((string) $tutorBalanceAfter->pending_amount, $pendingBefore, 2);
        $studentAvailableDelta = bcsub((string) $studentBalanceAfter->available_amount, $studentAvailableBefore, 2);
        $studentLockedDelta = bcsub((string) $studentBalanceAfter->locked_amount, $studentLockedBefore, 2);

        $this->assertSame('-31.39', $pendingDelta);
        $this->assertSame('38.00', $studentAvailableDelta, 'Gross share of the refunded lesson must go to student wallet because wallet was partially used.');
        $this->assertSame('-38.00', $studentLockedDelta);

        $child->refresh();
        $this->assertSame(Lesson::STATUS_CANCELLED, $child->status);
        $this->assertSame(Lesson::PAYMENT_REFUNDED, $child->payment_status);
    }
}
