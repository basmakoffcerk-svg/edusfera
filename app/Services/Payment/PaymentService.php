<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\Events\EventActor;
use App\Domain\Shared\Events\EventEnvelopeFactory;
use App\Integrations\Outbox\OutboxRepository;
use App\Models\Lesson;
use App\Models\LessonSettlement;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\Transaction;
use App\Models\TutorBalance;
use App\Notifications\LessonCancelledNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Services\ChatService;
use App\Services\Finance\StudentBalanceService;
use App\Services\StudentGoalService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly StudentBalanceService $studentBalanceService,
        private readonly StudentGoalService $studentGoalService,
        private readonly EventEnvelopeFactory $eventFactory,
        private readonly OutboxRepository $outbox,
    ) {}

    public function verifyPayment(string $transactionId): bool
    {
        return $this->gateway->verifyPayment($transactionId);
    }

    public function processPayment(
        int $lessonId,
        int $userId,
        ?string $paymentMethod = 'card',
        bool $rememberPaymentMethod = false,
        bool $useWalletBalance = false,
    ): Transaction {
        return DB::transaction(function () use ($lessonId, $paymentMethod, $rememberPaymentMethod, $useWalletBalance, $userId): Transaction {
            $lesson = Lesson::query()
                ->with(['tutor', 'student', 'parent', 'transaction'])
                ->lockForUpdate()
                ->findOrFail($lessonId);

            if (! in_array($userId, array_filter([$lesson->student_id, $lesson->parent_id]), true)) {
                throw ValidationException::withMessages([
                    'payment' => 'Оплатить урок может только ученик или родитель, оформивший запись.',
                ]);
            }

            if ($lesson->payment_status === Lesson::PAYMENT_PAID) {
                throw ValidationException::withMessages([
                    'payment' => 'Этот урок уже оплачен.',
                ]);
            }

            if (! $lesson->hasActivePaymentLock()) {
                throw ValidationException::withMessages([
                    'payment' => 'Резерв времени истек. Выберите слот заново.',
                ]);
            }

            $currency = (string) config('payments.currency', 'BYN');
            $lessonAmount = $this->money((string) $lesson->price);
            $payableAmount = $this->money((string) ($lesson->package_total ?? $lesson->price));
            $effectivePaymentMethod = (string) ($paymentMethod ?? 'card');
            $chargeAmount = $effectivePaymentMethod === 'wallet'
                ? '0.00'
                : $payableAmount;
            $studentBalance = $this->studentBalanceService->getOrCreate($userId);
            $walletContribution = '0.00';

            if ($effectivePaymentMethod !== 'wallet' && $useWalletBalance) {
                $walletContribution = $this->min((string) $studentBalance->available_amount, $payableAmount);
                $chargeAmount = $this->maxZero($this->sub($chargeAmount, $walletContribution));
            }

            $platformCommission = $this->multiply($payableAmount, (string) config('payments.commission_rate', '0.15'));
            $acquiringFee = $this->add(
                $this->multiply($payableAmount, (string) config('payments.acquiring_rate', '0.022')),
                $this->money((string) config('payments.acquiring_fixed', '0.30')),
            );
            $netAmount = $this->sub($this->sub($payableAmount, $platformCommission), $acquiringFee);

            if ($effectivePaymentMethod === 'wallet' || bccomp($chargeAmount, '0', 2) !== 1) {
                if (bccomp($chargeAmount, '0', 2) !== 1) {
                    $effectivePaymentMethod = 'wallet';
                }

                $gatewayResponse = [
                    'success' => true,
                    'source' => bccomp($walletContribution, '0', 2) === 1 ? 'wallet_partial' : 'wallet',
                    'charged_amount' => $payableAmount,
                ];
            } else {
                $gatewayResponse = $this->gateway->createPayment([
                    'lesson_id' => $lesson->id,
                    'user_id' => $userId,
                    'amount' => $chargeAmount,
                    'currency' => $currency,
                    'payment_method' => $effectivePaymentMethod,
                ]);
            }

            $status = ($gatewayResponse['status'] ?? 'success') === 'pending' 
                ? Transaction::STATUS_PENDING 
                : Transaction::STATUS_SUCCESS;

            $transaction = Transaction::query()->updateOrCreate(
                ['lesson_id' => $lesson->id],
                [
                    'user_id' => $userId,
                    'amount' => $payableAmount,
                    'platform_commission' => $platformCommission,
                    'acquiring_fee' => $acquiringFee,
                    'net_amount' => $netAmount,
                    'currency' => $currency,
                    'status' => $status,
                    'payment_method' => $effectivePaymentMethod,
                    'gateway_transaction_id' => $gatewayResponse['gateway_transaction_id'] ?? null,
                    'gateway_response' => array_merge($gatewayResponse, [
                        'remember_payment_method' => $rememberPaymentMethod,
                        'package_code' => $lesson->package_code,
                        'package_lessons' => $lesson->package_lessons,
                        'charged_amount' => $chargeAmount,
                        'lesson_amount' => $lessonAmount,
                        'payable_amount' => $payableAmount,
                        'wallet_contribution' => $walletContribution,
                    ]),
                    'paid_at' => $status === Transaction::STATUS_SUCCESS ? now('UTC') : null,
                ],
            );

            if (! ($gatewayResponse['success'] ?? false)) {
                $lesson->update(['payment_status' => Lesson::PAYMENT_UNPAID]);

                throw ValidationException::withMessages([
                    'payment' => 'Ошибка инициализации платежа: ' . ($gatewayResponse['message'] ?? 'Неизвестная ошибка'),
                ]);
            }

            if ($status === Transaction::STATUS_PENDING) {
                // Async gateway (e.g. bePaid): just return the pending transaction.
                // It contains the redirect_url in its gateway_response.
                return $transaction->fresh(['lesson', 'user']);
            }

            // Sync gateway (e.g. Wallet or Mock): capture immediately
            return $this->capturePendingPayment($transaction);
        });
    }

    
    public function capturePendingPayment(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction): Transaction {
            $transaction = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $lesson = Lesson::query()->with(['tutor', 'student', 'parent'])->lockForUpdate()->findOrFail($transaction->lesson_id);

            if ($lesson->payment_status === Lesson::PAYMENT_PAID) {
                return $transaction->fresh(['lesson', 'user']);
            }

            $userId = $transaction->user_id;
            $currency = $transaction->currency;
            $effectivePaymentMethod = $transaction->payment_method;
            $payableAmount = $transaction->amount;
            $platformCommission = $transaction->platform_commission;
            $acquiringFee = $transaction->acquiring_fee;
            $netAmount = $transaction->net_amount;

            $chargeAmount = $transaction->gateway_response['charged_amount'] ?? $payableAmount;
            $walletContribution = $transaction->gateway_response['wallet_contribution'] ?? '0.00';
            
            $studentBalance = $this->studentBalanceService->getOrCreate($userId);

            if ($effectivePaymentMethod !== 'wallet' && bccomp((string)$chargeAmount, '0', 2) === 1) {
                $this->studentBalanceService->credit(
                    balance: $studentBalance,
                    amount: (string)$chargeAmount,
                    currency: $currency,
                    type: StudentBalanceLedgerEntry::TYPE_TOPUP,
                    lesson: $lesson,
                    transaction: $transaction,
                    meta: [
                        'reason' => 'checkout_payment',
                        'package_code' => $lesson->package_code,
                        'wallet_contribution' => (string)$walletContribution,
                    ],
                );
            }

            $this->studentBalanceService->debitForLesson(
                balance: $studentBalance->fresh(),
                amount: (string)$payableAmount,
                currency: $currency,
                lesson: $lesson,
                transaction: $transaction,
                meta: [
                    'reason' => 'lesson_hold',
                    'package_code' => $lesson->package_code,
                    'wallet_contribution' => (string)$walletContribution,
                ],
            );

            $lesson->update([
                'payment_status' => Lesson::PAYMENT_PAID,
                'status' => Lesson::STATUS_CONFIRMED,
                'package_lessons_remaining' => max(((int) $lesson->package_lessons) - 1, 0),
                'payment_lock_expires_at' => null,
            ]);

            Lesson::query()
                ->where('package_parent_lesson_id', $lesson->id)
                ->update([
                    'payment_status' => Lesson::PAYMENT_PAID,
                    'status' => Lesson::STATUS_CONFIRMED,
                    'payment_lock_expires_at' => null,
                ]);

            $balance = TutorBalance::query()->firstOrCreate(
                ['user_id' => $lesson->tutor_id],
                [
                    'available_amount' => '0.00',
                    'pending_amount' => '0.00',
                    'total_earned' => '0.00',
                    'total_withdrawn' => '0.00',
                ],
            );

            $balance->update([
                'pending_amount' => $this->add((string) $balance->pending_amount, (string)$netAmount),
            ]);

            $this->logFinancialOperation('payment_processed', [
                'lesson_id' => $lesson->id,
                'user_id' => $userId,
                'tutor_id' => $lesson->tutor_id,
                'amount' => (string)$payableAmount,
                'charged_amount' => (string)$chargeAmount,
                'wallet_contribution' => (string)$walletContribution,
                'platform_commission' => (string)$platformCommission,
                'acquiring_fee' => (string)$acquiringFee,
                'net_amount' => (string)$netAmount,
                'transaction_id' => $transaction->id,
            ]);

            $this->outbox->append(
                $this->eventFactory->paymentCompleted(
                    data: [
                        'lesson_id' => $lesson->id,
                        'student_id' => $lesson->student_id,
                        'tutor_id' => $lesson->tutor_id,
                        'amount' => (string)$payableAmount,
                        'currency' => $currency,
                        'transaction_id' => $transaction->id,
                    ],
                    actor: new EventActor('user', $userId, 'student'),
                ),
            );

            $this->studentGoalService->ensureGoalForPaidLesson($lesson->fresh(['tutor.tutorProfile', 'student']));

            $lesson->student?->notify(new PaymentSucceededNotification($transaction));
            $lesson->tutor?->notify(new PaymentSucceededNotification($transaction));
            app(ChatService::class)->unlockContactsForLesson($lesson);

            $transaction->update([
                'status' => Transaction::STATUS_SUCCESS,
                'paid_at' => now('UTC'),
            ]);

            return $transaction->fresh(['lesson', 'user']);
        });
    }

    public function refundLessonPayment(Lesson $lesson, ?string $reason = null): void
    {
        DB::transaction(function () use ($lesson, $reason): void {
            $lesson = Lesson::query()->lockForUpdate()->findOrFail($lesson->id);

            // Resolve the backing transaction. Child package lessons own no
            // Transaction (UNIQUE transactions.lesson_id binds it to the
            // parent), so we look it up via package_parent_lesson_id.
            $transaction = $this->resolvePackageTransaction($lesson);

            // Unpaid lessons (or lessons without any backing transaction) are
            // simply cancelled — no financial movement. Preserves the original
            // guard behaviour for the single-lesson case.
            if ($lesson->payment_status !== Lesson::PAYMENT_PAID || $transaction === null) {
                $lesson->update(['status' => Lesson::STATUS_CANCELLED]);

                return;
            }

            $packageLessons = $this->resolvePackageLessons($lesson, $transaction);

            // A "single" refund is a lesson that is neither a package child nor
            // a multi-lesson package parent — its full transaction is refunded.
            $isSingle = $packageLessons <= 1 && $lesson->package_parent_lesson_id === null;

            if ($isSingle) {
                $this->refundSingleLesson($lesson, $transaction, $reason);

                return;
            }

            $this->refundPackageLesson($lesson, $transaction, $packageLessons, $reason);
        });
    }

    /**
     * Single (non-package) refund. Semantically equivalent to the pre-fix
     * behaviour: refund the full transaction.amount, zero this lesson's
     * net_amount out of pending, flip the lesson to cancelled/refunded — plus
     * an audit LessonSettlement row.
     */
    private function refundSingleLesson(Lesson $lesson, Transaction $transaction, ?string $reason): void
    {
        $gatewayTransactionId = $transaction->gateway_transaction_id ?? (string) $transaction->id;
        $chargedAmount = (string) ($transaction->gateway_response['charged_amount'] ?? $transaction->amount);
        $isWalletPayment = $transaction->payment_method === 'wallet';
        $refundToWallet = $isWalletPayment || bccomp($chargedAmount, (string) $transaction->amount, 2) === -1;
        $refundSource = $refundToWallet ? 'wallet' : 'gateway';

        if (! $refundToWallet) {
            if (! $this->gateway->refundPayment($gatewayTransactionId, (float) $transaction->amount)) {
                throw ValidationException::withMessages([
                    'payment' => 'Не удалось выполнить возврат.',
                ]);
            }
        }

        $transaction->update([
            'status' => Transaction::STATUS_REFUNDED,
            'gateway_response' => array_merge($transaction->gateway_response ?? [], [
                'refund_reason' => $reason,
                'refunded_at' => now('UTC')->toISOString(),
                'refund_source' => $refundSource,
            ]),
        ]);

        $lesson->update([
            'status' => Lesson::STATUS_CANCELLED,
            'payment_status' => Lesson::PAYMENT_REFUNDED,
        ]);

        $balance = TutorBalance::query()->firstOrCreate(
            ['user_id' => $lesson->tutor_id],
            [
                'available_amount' => '0.00',
                'pending_amount' => '0.00',
                'total_earned' => '0.00',
                'total_withdrawn' => '0.00',
            ],
        );

        $balance->update([
            'pending_amount' => $this->maxZero(
                $this->sub((string) $balance->pending_amount, (string) $transaction->net_amount)
            ),
        ]);

        $studentBalance = $this->studentBalanceService->getOrCreate($transaction->user_id);

        if ($refundToWallet) {
            $this->studentBalanceService->releaseHeldForLesson(
                balance: $studentBalance,
                amount: (string) $transaction->amount,
                currency: (string) $transaction->currency,
                lesson: $lesson,
                transaction: $transaction,
                meta: [
                    'reason' => $reason ?: 'lesson_release',
                ],
            );
        } else {
            $this->studentBalanceService->refundHeldExternally(
                balance: $studentBalance,
                amount: (string) $transaction->amount,
                currency: (string) $transaction->currency,
                lesson: $lesson,
                transaction: $transaction,
                meta: [
                    'reason' => $reason ?: 'external_refund',
                ],
            );
        }

        // Audit-only LessonSettlement. A concurrent insert racing on the UNIQUE
        // lesson_id means the audit row already exists — skip it, the financial
        // movement above is intentionally unguarded.
        try {
            LessonSettlement::query()->create([
                'lesson_id' => $lesson->id,
                'transaction_id' => $transaction->id,
                'net_share' => '0.00',
                'gross_share' => '0.00',
                'refunded_at' => now('UTC'),
                'meta' => [
                    'refunded_net' => (string) $transaction->net_amount,
                    'refunded_gross' => (string) $transaction->amount,
                    'refund_source' => $refundSource,
                ],
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }
        }

        $this->logFinancialOperation('payment_refunded', [
            'lesson_id' => $lesson->id,
            'transaction_id' => $transaction->id,
            'tutor_id' => $lesson->tutor_id,
            'net_amount' => (string) $transaction->net_amount,
            'reason' => $reason,
        ]);

        $lesson->student?->notify(new LessonCancelledNotification($lesson));
        $lesson->tutor?->notify(new LessonCancelledNotification($lesson));
    }

    /**
     * Partial (per-lesson) refund of one package lesson. Returns only this
     * lesson's gross share to the student, decrements only its net share from
     * the tutor's pending balance, leaves already-settled shares in
     * available_amount untouched, and never touches sibling lessons.
     */
    private function refundPackageLesson(Lesson $lesson, Transaction $transaction, int $packageLessons, ?string $reason): void
    {
        // Lock the transaction and all its settlements to serialize
        // against concurrent settle/refund of sibling package lessons.
        $tx = Transaction::query()->lockForUpdate()->find($transaction->id);

        if ($tx === null) {
            return;
        }

        $settlements = LessonSettlement::query()
            ->where('transaction_id', $tx->id)
            ->lockForUpdate()
            ->get();

        $existing = $settlements->firstWhere('lesson_id', $lesson->id);

        // A settled lesson cannot be refunded.
        if ($existing !== null && $existing->settled_at !== null) {
            throw ValidationException::withMessages([
                'payment' => 'Урок уже проведён, возврат недоступен.',
            ]);
        }

        // Idempotency: this lesson is already refunded.
        if ($existing !== null && $existing->refunded_at !== null) {
            return;
        }

        // Compute this lesson's share. "Closed" lessons (settled OR
        // refunded) are both subtracted when resolving the residual so the
        // package always closes out to exactly transaction totals.
        $closedSettlements = $settlements->filter(
            fn (LessonSettlement $settlement): bool => $settlement->settled_at !== null || $settlement->refunded_at !== null
        );

        $alreadyClosedNet = $this->sumClosedShares($closedSettlements, 'net_share', 'refunded_net');
        $alreadyClosedGross = $this->sumClosedShares($closedSettlements, 'gross_share', 'refunded_gross');
        $closedCount = $closedSettlements->count();

        $remainingLessons = $packageLessons - $closedCount;
        $isResidual = $remainingLessons <= 1;

        if ($isResidual) {
            $netShare = bcsub((string) $tx->net_amount, $alreadyClosedNet, 2);
            $grossShare = bcsub((string) $tx->amount, $alreadyClosedGross, 2);
        } else {
            $netShare = bcdiv((string) $tx->net_amount, (string) $packageLessons, 2);
            $grossShare = bcdiv((string) $tx->amount, (string) $packageLessons, 2);
        }

        // Reduce the tutor's pending balance by this lesson's net share.
        // available_amount is NOT touched — already-settled shares stay earned.
        $balance = TutorBalance::query()->firstOrCreate(
            ['user_id' => $lesson->tutor_id],
            [
                'available_amount' => '0.00',
                'pending_amount' => '0.00',
                'total_earned' => '0.00',
                'total_withdrawn' => '0.00',
            ],
        );

        $balance->update([
            'pending_amount' => $this->maxZero($this->sub((string) $balance->pending_amount, $netShare)),
        ]);

        // Refund only this lesson's gross share to the student.
        $gatewayTransactionId = $tx->gateway_transaction_id ?? (string) $tx->id;
        $chargedAmount = (string) ($tx->gateway_response['charged_amount'] ?? $tx->amount);
        $isWalletPayment = $tx->payment_method === 'wallet';
        $refundToWallet = $isWalletPayment || bccomp($chargedAmount, (string) $tx->amount, 2) === -1;
        $refundSource = $refundToWallet ? 'wallet' : 'gateway';

        $studentBalance = $this->studentBalanceService->getOrCreate($tx->user_id);

        if ($refundToWallet) {
            $this->studentBalanceService->releaseHeldForLesson(
                balance: $studentBalance,
                amount: $grossShare,
                currency: (string) $tx->currency,
                lesson: $lesson,
                transaction: $tx,
                meta: [
                    'reason' => $reason ?: 'lesson_release_partial',
                ],
            );
        } else {
            if (! $this->gateway->refundPayment($gatewayTransactionId, (float) $grossShare)) {
                throw ValidationException::withMessages([
                    'payment' => 'Не удалось выполнить возврат.',
                ]);
            }

            $this->studentBalanceService->refundHeldExternally(
                balance: $studentBalance,
                amount: $grossShare,
                currency: (string) $tx->currency,
                lesson: $lesson,
                transaction: $tx,
                meta: [
                    'reason' => $reason ?: 'external_refund_partial',
                ],
            );
        }

        // Record the refund. net_share/gross_share stay 0 (a refund is
        // not earnings); the real amounts are kept in meta for audit/residual.
        // A concurrent insert racing on UNIQUE lesson_id means this lesson was
        // already refunded, so we return idempotently.
        try {
            LessonSettlement::query()->create([
                'lesson_id' => $lesson->id,
                'transaction_id' => $tx->id,
                'net_share' => '0.00',
                'gross_share' => '0.00',
                'refunded_at' => now('UTC'),
                'meta' => [
                    'refunded_net' => $netShare,
                    'refunded_gross' => $grossShare,
                    'refund_source' => $refundSource,
                    'reason' => $reason,
                    'is_residual' => $isResidual,
                ],
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                return;
            }

            throw $exception;
        }

        // Only this lesson is cancelled/refunded; siblings are untouched.
        $lesson->update([
            'status' => Lesson::STATUS_CANCELLED,
            'payment_status' => Lesson::PAYMENT_REFUNDED,
        ]);

        // Recompute the transaction status from the package's settlements.
        $afterSettlements = LessonSettlement::query()
            ->where('transaction_id', $tx->id)
            ->get();

        $settledCount = $afterSettlements->filter(
            fn (LessonSettlement $settlement): bool => $settlement->settled_at !== null
        )->count();
        $refundedCount = $afterSettlements->filter(
            fn (LessonSettlement $settlement): bool => $settlement->refunded_at !== null
        )->count();

        if ($refundedCount === $packageLessons) {
            $transactionStatus = Transaction::STATUS_REFUNDED;
        } elseif (($settledCount + $refundedCount) === $packageLessons && $refundedCount > 0) {
            $transactionStatus = Transaction::STATUS_PARTIALLY_REFUNDED;
        } else {
            $transactionStatus = Transaction::STATUS_SUCCESS;
        }

        // Preserve any existing flags (e.g. gateway_response.settled) — only
        // append refund metadata.
        $tx->update([
            'status' => $transactionStatus,
            'gateway_response' => array_merge($tx->gateway_response ?? [], [
                'refund_reason' => $reason,
                'refunded_at' => now('UTC')->toISOString(),
                'refund_source' => $refundSource,
            ]),
        ]);

        // Audit log.
        $this->logFinancialOperation('lesson_refunded_partial', [
            'lesson_id' => $lesson->id,
            'transaction_id' => $tx->id,
            'refunded_net' => $netShare,
            'refunded_gross' => $grossShare,
            'is_residual' => $isResidual,
            'refund_source' => $refundSource,
            'transaction_status_after' => $transactionStatus,
            'tutor_id' => $lesson->tutor_id,
        ]);

        $lesson->student?->notify(new LessonCancelledNotification($lesson));
        $lesson->tutor?->notify(new LessonCancelledNotification($lesson));
    }

    /**
     * Sum closed (settled or refunded) share amounts. Settled rows carry the
     * real amount in their net_share/gross_share column; refunded rows keep
     * net_share/gross_share at 0 and store the real amount under meta.
     *
     * @param  \Illuminate\Support\Collection<int, LessonSettlement>  $settlements
     */
    private function sumClosedShares($settlements, string $column, string $metaKey): string
    {
        return $settlements->reduce(
            function (string $carry, LessonSettlement $settlement) use ($column, $metaKey): string {
                $amount = $settlement->settled_at !== null
                    ? (string) $settlement->{$column}
                    : (string) (($settlement->meta[$metaKey] ?? '0.00'));

                return bcadd($carry, $amount, 2);
            },
            '0.00',
        );
    }

    public function settleCompletedLesson(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson): void {
            $lesson = Lesson::query()->lockForUpdate()->findOrFail($lesson->id);

            // 3.1 — A completed, paid lesson is required. The transaction is
            // resolved via the package parent for child lessons (which never
            // own their own Transaction), so we must NOT bail on a missing
            // own-transaction here.
            if ($lesson->status !== Lesson::STATUS_COMPLETED || $lesson->payment_status !== Lesson::PAYMENT_PAID) {
                return;
            }

            $transaction = $this->resolvePackageTransaction($lesson);

            if ($transaction === null || $transaction->status !== Transaction::STATUS_SUCCESS) {
                return;
            }

            // 3.2 — Lock the transaction and all its settlements for the
            // duration of this settlement to serialize concurrent completions
            // of sibling package lessons.
            $tx = Transaction::query()->lockForUpdate()->find($transaction->id);

            if ($tx === null || $tx->status !== Transaction::STATUS_SUCCESS) {
                return;
            }

            $settlements = LessonSettlement::query()
                ->where('transaction_id', $tx->id)
                ->lockForUpdate()
                ->get();

            // 3.2 — Idempotency: this lesson is already settled or refunded.
            $existing = $settlements->firstWhere('lesson_id', $lesson->id);

            if ($existing !== null && ($existing->settled_at !== null || $existing->refunded_at !== null)) {
                return;
            }

            if (($tx->gateway_response['settled'] ?? false) === true) {
                return;
            }

            // 3.3 — Resolve package size and compute this lesson's share.
            $packageLessons = $this->resolvePackageLessons($lesson, $tx);

            $settledSettlements = $settlements->filter(
                fn (LessonSettlement $settlement): bool => $settlement->settled_at !== null
            );

            $alreadySettledNet = $this->sumShares($settledSettlements, 'net_share');
            $alreadySettledGross = $this->sumShares($settledSettlements, 'gross_share');
            $settledCount = $settledSettlements->count();

            $isResidual = ($settledCount + 1) === $packageLessons;

            if ($packageLessons <= 1) {
                // Single lesson (or package metadata absent): settle the full
                // transaction in one shot.
                $netShare = (string) $tx->net_amount;
                $grossShare = (string) $tx->amount;
                $isResidual = true;
            } elseif ($isResidual) {
                // Last lesson of the package: close out the exact remainder so
                // Σ shares == transaction totals to the kopeck.
                $netShare = bcsub((string) $tx->net_amount, $alreadySettledNet, 2);
                $grossShare = bcsub((string) $tx->amount, $alreadySettledGross, 2);
            } else {
                // Base share: bcdiv with scale 2 truncates (rounds down).
                $netShare = bcdiv((string) $tx->net_amount, (string) $packageLessons, 2);
                $grossShare = bcdiv((string) $tx->amount, (string) $packageLessons, 2);
            }

            // 3.4 — Record the settlement. A concurrent insert racing on the
            // UNIQUE lesson_id constraint means another worker already settled
            // this lesson, so we return idempotently.
            try {
                LessonSettlement::query()->create([
                    'lesson_id' => $lesson->id,
                    'transaction_id' => $tx->id,
                    'net_share' => $netShare,
                    'gross_share' => $grossShare,
                    'settled_at' => now('UTC'),
                    'meta' => [
                        'package_lessons' => $packageLessons,
                        'is_residual' => $isResidual,
                        'settled_count_after' => $settledCount + 1,
                    ],
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueViolation($exception)) {
                    return;
                }

                throw $exception;
            }

            // 3.5 — Credit the tutor only for this lesson's net share.
            $balance = TutorBalance::query()->firstOrCreate(
                ['user_id' => $lesson->tutor_id],
                [
                    'available_amount' => '0.00',
                    'pending_amount' => '0.00',
                    'total_earned' => '0.00',
                    'total_withdrawn' => '0.00',
                ],
            );

            $balance->update([
                'pending_amount' => $this->maxZero($this->sub((string) $balance->pending_amount, $netShare)),
                'available_amount' => $this->add((string) $balance->available_amount, $netShare),
                'total_earned' => $this->add((string) $balance->total_earned, $netShare),
            ]);

            // 3.6 — Capture only this lesson's gross share from the hold.
            $studentBalance = $this->studentBalanceService->getOrCreate($tx->user_id);
            $this->studentBalanceService->captureHeldForLesson(
                balance: $studentBalance,
                amount: $grossShare,
                currency: (string) $tx->currency,
                lesson: $lesson,
                transaction: $tx,
                meta: [
                    'reason' => 'lesson_completed_partial',
                    'net_share' => $netShare,
                    'is_residual' => $isResidual,
                ],
            );

            // 3.7 — On the lesson that closes the package, mark the transaction
            // settled for backward-compat / audit. This is NOT the idempotency
            // source of truth — LessonSettlement is.
            if (($settledCount + 1) === $packageLessons) {
                $tx->update([
                    'gateway_response' => array_merge($tx->gateway_response ?? [], [
                        'settled' => true,
                        'settled_at' => now('UTC')->toISOString(),
                    ]),
                ]);
            }

            // 3.8 — Audit log.
            $this->logFinancialOperation('lesson_settled_partial', [
                'lesson_id' => $lesson->id,
                'transaction_id' => $tx->id,
                'net_share' => $netShare,
                'gross_share' => $grossShare,
                'is_residual' => $isResidual,
                'package_lessons' => $packageLessons,
                'settled_count_after' => $settledCount + 1,
                'tutor_id' => $lesson->tutor_id,
            ]);
        });
    }

    /**
     * Resolve the package/single Transaction that backs a lesson. Child
     * package lessons (package_parent_lesson_id != null) have no own
     * Transaction, so we look it up via the parent lesson.
     */
    private function resolvePackageTransaction(Lesson $lesson): ?Transaction
    {
        if ($lesson->package_parent_lesson_id !== null) {
            return Transaction::query()
                ->where('lesson_id', $lesson->package_parent_lesson_id)
                ->first();
        }

        return $lesson->transaction()->first();
    }

    /**
     * Resolve the number of lessons in the package. Prefer the value recorded
     * on the transaction at processPayment time, falling back to the parent
     * lesson's package_lessons. Defaults to 1 (single) when unavailable.
     */
    private function resolvePackageLessons(Lesson $lesson, Transaction $transaction): int
    {
        $fromGateway = (int) (($transaction->gateway_response['package_lessons'] ?? 0));

        if ($fromGateway > 0) {
            return $fromGateway;
        }

        if ($lesson->package_parent_lesson_id !== null) {
            $parent = Lesson::query()->find($lesson->package_parent_lesson_id);

            if ($parent !== null && (int) $parent->package_lessons > 0) {
                return (int) $parent->package_lessons;
            }
        }

        return max((int) $lesson->package_lessons, 1);
    }

    /**
     * Sum a decimal share column across a collection of settlements using
     * bcadd with scale 2.
     *
     * @param  \Illuminate\Support\Collection<int, LessonSettlement>  $settlements
     */
    private function sumShares($settlements, string $column): string
    {
        return $settlements->reduce(
            fn (string $carry, LessonSettlement $settlement): string => bcadd($carry, (string) $settlement->{$column}, 2),
            '0.00',
        );
    }

    /**
     * Detect a UNIQUE constraint violation (SQLSTATE 23000) from a concurrent
     * insert racing on lesson_settlements.lesson_id.
     */
    private function isUniqueViolation(QueryException $exception): bool
    {
        return (string) ($exception->getCode()) === '23000'
            || ((int) ($exception->errorInfo[1] ?? 0)) === 1062 // MySQL duplicate key
            || str_contains(strtolower($exception->getMessage()), 'unique');
    }

    public function requestPayout(int $tutorId): TutorBalance
    {
        return DB::transaction(function () use ($tutorId): TutorBalance {
            $balance = TutorBalance::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $tutorId],
                [
                    'available_amount' => '0.00',
                    'pending_amount' => '0.00',
                    'total_earned' => '0.00',
                    'total_withdrawn' => '0.00',
                ],
            );

            $this->logFinancialOperation('payout_requested', [
                'tutor_id' => $tutorId,
                'available_amount' => (string) $balance->available_amount,
            ]);

            $balance->update([
                'last_payout_at' => now('UTC'),
            ]);

            return $balance;
        });
    }

    private function money(string $amount): string
    {
        return $this->roundMoney($amount);
    }

    private function add(string $left, string $right): string
    {
        return $this->roundMoney(bcadd($left, $right, 4));
    }

    private function sub(string $left, string $right): string
    {
        return $this->roundMoney(bcsub($left, $right, 4));
    }

    private function multiply(string $left, string $right): string
    {
        return $this->roundMoney(bcmul($left, $right, 4));
    }

    private function roundMoney(string $amount): string
    {
        $adjustment = bccomp($amount, '0', 4) >= 0 ? '0.005' : '-0.005';

        return bcadd($amount, $adjustment, 2);
    }

    private function maxZero(string $amount): string
    {
        return bccomp($amount, '0', 2) === -1 ? '0.00' : $amount;
    }

    private function min(string $left, string $right): string
    {
        return bccomp($left, $right, 2) <= 0 ? $left : $right;
    }

    private function logFinancialOperation(string $event, array $context): void
    {
        Log::channel('payments')->info($event, $context + [
            'logged_at' => now('UTC')->toISOString(),
        ]);
    }
}
