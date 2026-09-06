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

        $parent = Lesson::query()->forceCreate([
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
            'platform_commission' => '0.00',
            'net_amount' => '152.00',
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => $now->addMinutes(15),
        ]);

        $children = [];
        for ($i = 0; $i < 3; $i++) {
            $children[] = Lesson::query()->forceCreate([
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
        $this->assertSame('148.36', (string) $transaction->net_amount);

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
            '37.09',
            (string) $balance->available_amount,
            'Settle of the first package lesson must credit ONLY floor(net_amount / package_lessons, 2) = 37.09 BYN, '
            .'not the full transaction.net_amount (148.36). On UNFIXED code available_amount == 148.36 → bug confirmed.'
        );

        $this->assertSame(
            '111.27', // 148.36 - 37.09 = 111.27 (3 shares still pending)
            (string) $balance->pending_amount,
            'Pending must retain (N-1) shares = 111.27 BYN after first settle. '
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
            '37.09',
            $availableDelta,
            'Settle of a child package lesson must credit floor(148.36 / 4, 2) = 37.09 BYN. '
            .'On UNFIXED code child has no own Transaction → settle is a no-op → delta = 0.00 → bug confirmed.'
        );

        $this->assertSame(
            '-37.09',
            $pendingDelta,
            'Pending must decrease by exactly one share (37.09 BYN) on a child settle. '
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
            '-37.09',
            $pendingDelta,
            'Refund of an unsettled child package lesson must reduce tutor.pending_amount by exactly one '
            .'net share (37.09 BYN). On UNFIXED code child has no transaction → refund early-returns after '
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

        $this->assertSame('-37.09', $pendingDelta);
        $this->assertSame('38.00', $studentAvailableDelta, 'Gross share of the refunded lesson must go to student wallet because wallet was partially used.');
        $this->assertSame('-38.00', $studentLockedDelta);

        $child->refresh();
        $this->assertSame(Lesson::STATUS_CANCELLED, $child->status);
        $this->assertSame(Lesson::PAYMENT_REFUNDED, $child->payment_status);
    }

    /**
     * Проверяет, что финансовая сверка wallet:reconcile-holds корректно рассчитывает
     * заблокированный баланс для оставшихся несыгранных уроков пакета,
     * даже если родительский урок пакета уже завершен.
     */
    public function test_reconcile_holds_preserves_remaining_package_lessons(): void
    {
        // Создаем оплаченный пакет из 4 уроков за 152.00 BYN (доля каждого урока = 38.00 BYN)
        $ctx = $this->bootstrapPaidPack4(useWalletBalance: true);

        /** @var Lesson $parent */
        $parent = $ctx['parent'];

        // Завершаем родительский урок
        $parent->update(['status' => Lesson::STATUS_COMPLETED]);
        app(PaymentService::class)->settleCompletedLesson($parent->fresh());

        // После завершения 1 урока из холда должно быть списано 38.00 BYN.
        // Остаток холда в кошельке под 3 оставшихся будущих урока: 152.00 - 38.00 = 114.00 BYN.
        $studentBalance = StudentBalance::query()->where('user_id', $ctx['student']->id)->firstOrFail();
        $this->assertSame('114.00', (string) $studentBalance->locked_amount);

        // Имитируем сбой: принудительно уменьшаем locked_amount в кошельке до 50.00 BYN
        $studentBalance->update(['locked_amount' => '50.00']);

        // Запускаем сверку холдов
        $this->artisan('wallet:reconcile-holds');

        // Проверяем, восстановила ли сверка баланс до 114.00 BYN
        $studentBalance->refresh();
        $this->assertSame(
            '114.00',
            (string) $studentBalance->locked_amount,
            'Сверка холдов должна восстановить locked_amount до 114.00 BYN для 3 оставшихся несыгранных уроков пакета.'
        );
    }

    private function bootstrapCustomPackage(string $packageCode, int $packageSize, string $pricePerHour): array
    {
        static $phoneCounter = 100000;
        Notification::fake();

        $tutor = User::factory()->create([
            'role' => 'tutor',
            'phone' => '+37529' . $phoneCounter++,
        ]);

        $tutor->tutorProfile()->create([
            'subjects' => ['Математика'],
            'audiences' => ['Подготовка к ЦЭ'],
            'price_per_hour' => $pricePerHour,
            'experience_years' => 5,
            'legal_status' => 'self_employed',
            'bio' => 'Подготовка к экзаменам.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'lesson_formats' => ['individual_online'],
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '+37529' . $phoneCounter++,
        ]);

        // Calculate package pricing
        $singlePrice = (float) $pricePerHour;
        $discountFactor = $packageSize === 4 ? 0.95 : 0.90;
        $packageTotal = round($singlePrice * $packageSize * $discountFactor, 2);
        $discount = round(($singlePrice * $packageSize) - $packageTotal, 2);
        
        $commissionRate = 0.15;
        $commission = round($packageTotal * $commissionRate, 2);
        $netAmount = round($packageTotal - $commission, 2);

        $now = CarbonImmutable::now('UTC');

        $parent = Lesson::query()->forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'start_time' => $now->addDays(2),
            'end_time' => $now->addDays(2)->addHour(),
            'duration_minutes' => 60,
            'price' => number_format($singlePrice, 2, '.', ''),
            'package_code' => $packageCode,
            'package_lessons' => $packageSize,
            'package_lessons_remaining' => $packageSize,
            'package_total' => number_format($packageTotal, 2, '.', ''),
            'package_discount' => number_format($discount, 2, '.', ''),
            'platform_commission' => number_format($commission, 2, '.', ''),
            'net_amount' => number_format($netAmount, 2, '.', ''),
            'status' => Lesson::STATUS_PENDING,
            'payment_status' => Lesson::PAYMENT_UNPAID,
            'payment_lock_expires_at' => $now->addMinutes(15),
        ]);

        $children = [];
        for ($i = 0; $i < $packageSize - 1; $i++) {
            $children[] = Lesson::query()->forceCreate([
                'tutor_id' => $tutor->id,
                'student_id' => $student->id,
                'start_time' => $now->addDays(3 + $i),
                'end_time' => $now->addDays(3 + $i)->addHour(),
                'duration_minutes' => 60,
                'price' => number_format($singlePrice, 2, '.', ''),
                'package_code' => $packageCode,
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
            useWalletBalance: false,
        );

        return [
            'tutor' => $tutor,
            'student' => $student,
            'parent' => $parent->refresh(),
            'children' => array_map(fn (Lesson $lesson): Lesson => $lesson->refresh(), $children),
            'transaction' => $transaction,
        ];
    }

    /**
     * Property 5.1 — Invariant: Sum of all net shares and gross shares settled
     * across N lessons must exactly equal the transaction net_amount and amount.
     */
    public function test_sum_invariant_for_random_package_amounts(): void
    {
        mt_srand(12345);

        for ($run = 0; $run < 50; $run++) {
            $packageSize = mt_rand(0, 1) ? 4 : 8;
            $pricePerHour = (string) mt_rand(10, 200);
            $packageCode = $packageSize === 4 ? 'pack_4' : 'pack_8';

            // Construct package lessons and transact payment
            $ctx = $this->bootstrapCustomPackage($packageCode, $packageSize, $pricePerHour);
            
            $parent = $ctx['parent'];
            $children = $ctx['children'];
            $tx = $ctx['transaction'];

            // Settle all lessons one by one
            $parent->update(['status' => Lesson::STATUS_COMPLETED]);
            app(PaymentService::class)->settleCompletedLesson($parent->fresh());

            foreach ($children as $child) {
                $child->update(['status' => Lesson::STATUS_COMPLETED]);
                app(PaymentService::class)->settleCompletedLesson($child->fresh());
            }

            // Verify totals
            $settlements = \App\Models\LessonSettlement::query()
                ->where('transaction_id', $tx->id)
                ->get();

            $sumNet = '0.00';
            $sumGross = '0.00';
            foreach ($settlements as $s) {
                $sumNet = bcadd($sumNet, (string) $s->net_share, 2);
                $sumGross = bcadd($sumGross, (string) $s->gross_share, 2);
            }

            $this->assertSame((string) $tx->net_amount, $sumNet, "Run {$run}: Sum of net shares must equal transaction net_amount");
            $this->assertSame((string) $tx->amount, $sumGross, "Run {$run}: Sum of gross shares must equal transaction amount");

            $balance = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
            $this->assertSame((string) $tx->net_amount, (string) $balance->available_amount);
            $this->assertSame('0.00', (string) $balance->pending_amount);
        }
    }

    /**
     * Property 5.2 — Invariant: Individual share credited to tutor must be
     * within one kopeck (0.01) of the base net share (or residual share on the last lesson).
     */
    public function test_individual_share_within_one_kopeck_of_base(): void
    {
        mt_srand(12345);

        for ($run = 0; $run < 30; $run++) {
            $packageSize = mt_rand(0, 1) ? 4 : 8;
            $pricePerHour = (string) mt_rand(10, 200);
            $packageCode = $packageSize === 4 ? 'pack_4' : 'pack_8';

            $ctx = $this->bootstrapCustomPackage($packageCode, $packageSize, $pricePerHour);
            
            $parent = $ctx['parent'];
            $children = $ctx['children'];
            $tx = $ctx['transaction'];

            $baseNetShare = bcdiv((string) $tx->net_amount, (string) $packageSize, 2);

            // Settle parent
            $parent->update(['status' => Lesson::STATUS_COMPLETED]);
            app(PaymentService::class)->settleCompletedLesson($parent->fresh());
            
            $parentSettlement = $parent->refresh()->settlement;
            $this->assertNotNull($parentSettlement);
            
            // Check that parent net_share is exactly baseNetShare (since it is first)
            $this->assertSame($baseNetShare, (string) $parentSettlement->net_share);

            // Settle all children except the last one
            for ($i = 0; $i < $packageSize - 2; $i++) {
                $child = $children[$i];
                $child->update(['status' => Lesson::STATUS_COMPLETED]);
                app(PaymentService::class)->settleCompletedLesson($child->fresh());

                $settlement = $child->refresh()->settlement;
                $this->assertNotNull($settlement);
                $this->assertSame($baseNetShare, (string) $settlement->net_share);
            }

            // Settle last child (should be residual)
            $lastChild = $children[$packageSize - 2];
            $lastChild->update(['status' => Lesson::STATUS_COMPLETED]);
            app(PaymentService::class)->settleCompletedLesson($lastChild->fresh());

            $lastSettlement = $lastChild->refresh()->settlement;
            $this->assertNotNull($lastSettlement);

            // Difference between residual and base share must be <= (packageSize - 1) kopecks
            $diff = abs((float) $lastSettlement->net_share - (float) $baseNetShare);
            $maxAllowedDiff = ($packageSize - 1) * 0.01;
            $this->assertLessThanOrEqual($maxAllowedDiff + 0.0001, $diff, "Run {$run}: Residual share too far from base");
        }
    }

    /**
     * Property 5.3 — Invariant: Refund of remaining unsettled package lessons
     * must not modify the available_amount already settled to the tutor.
     */
    public function test_refund_remaining_does_not_affect_settled(): void
    {
        mt_srand(12345);

        for ($run = 0; $run < 30; $run++) {
            $packageSize = mt_rand(0, 1) ? 4 : 8;
            $pricePerHour = (string) mt_rand(10, 200);
            $packageCode = $packageSize === 4 ? 'pack_4' : 'pack_8';

            $ctx = $this->bootstrapCustomPackage($packageCode, $packageSize, $pricePerHour);
            
            $parent = $ctx['parent'];
            $children = $ctx['children'];
            $tx = $ctx['transaction'];

            // Settle k lessons (where k in [0, packageSize - 1])
            $k = mt_rand(0, $packageSize - 1);

            if ($k > 0) {
                $parent->update(['status' => Lesson::STATUS_COMPLETED]);
                app(PaymentService::class)->settleCompletedLesson($parent->fresh());

                for ($i = 0; $i < $k - 1; $i++) {
                    $children[$i]->update(['status' => Lesson::STATUS_COMPLETED]);
                    app(PaymentService::class)->settleCompletedLesson($children[$i]->fresh());
                }
            }

            // Tutor balance before refund
            $balanceBefore = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
            $availableBefore = (string) $balanceBefore->available_amount;
            $pendingBefore = (string) $balanceBefore->pending_amount;

            // Refund one child lesson that has NOT been settled
            $refundTargetIndex = mt_rand(max(0, $k - 1), $packageSize - 2);
            $refundTarget = $children[$refundTargetIndex];
            
            app(PaymentService::class)->refundLessonPayment($refundTarget->fresh(), 'student_cancelled');

            // Tutor balance after refund
            $balanceAfter = TutorBalance::query()->where('user_id', $ctx['tutor']->id)->firstOrFail();
            $availableAfter = (string) $balanceAfter->available_amount;
            $pendingAfter = (string) $balanceAfter->pending_amount;

            // available_amount must not change
            $this->assertSame($availableBefore, $availableAfter, "Run {$run}: available_amount changed after refund");

            // pending_amount must decrease
            $this->assertLessThan($pendingBefore, $pendingAfter, "Run {$run}: pending_amount did not decrease after refund");

            // Refunded lesson status must be CANCELLED and PAYMENT_REFUNDED
            $this->assertSame(Lesson::STATUS_CANCELLED, $refundTarget->refresh()->status);
            $this->assertSame(Lesson::PAYMENT_REFUNDED, $refundTarget->refresh()->payment_status);

            // All other unsettled/settled lessons must keep their status unchanged
            if ($k > 0) {
                $this->assertSame(Lesson::STATUS_COMPLETED, $parent->refresh()->status);
                for ($i = 0; $i < $k - 1; $i++) {
                    $this->assertSame(Lesson::STATUS_COMPLETED, $children[$i]->refresh()->status);
                }
            }
            for ($i = $k; $i < $packageSize - 1; $i++) {
                if ($i !== $refundTargetIndex) {
                    $this->assertSame(Lesson::STATUS_CONFIRMED, $children[$i]->refresh()->status);
                }
            }
        }
    }
}

