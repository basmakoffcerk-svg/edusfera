<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Lesson;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\Transaction;
use App\Models\TutorBalance;
use App\Notifications\LessonCancelledNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Services\ChatService;
use App\Services\Finance\StudentBalanceService;
use App\Services\StudentGoalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly StudentBalanceService $studentBalanceService,
        private readonly StudentGoalService $studentGoalService,
    )
    {
    }

    public function processPayment(
        int $lessonId,
        int $userId,
        ?string $paymentMethod = 'card',
        bool $rememberPaymentMethod = false,
        bool $useWalletBalance = false,
    ): Transaction
    {
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
                ? $payableAmount
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

            $transaction = Transaction::query()->updateOrCreate(
                ['lesson_id' => $lesson->id],
                [
                    'user_id' => $userId,
                    'amount' => $payableAmount,
                    'platform_commission' => $platformCommission,
                    'acquiring_fee' => $acquiringFee,
                    'net_amount' => $netAmount,
                    'currency' => $currency,
                    'status' => ($gatewayResponse['success'] ?? false) ? Transaction::STATUS_SUCCESS : Transaction::STATUS_FAILED,
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
                    'paid_at' => ($gatewayResponse['success'] ?? false) ? now('UTC') : null,
                ],
            );

            if (! ($gatewayResponse['success'] ?? false)) {
                $lesson->update(['payment_status' => Lesson::PAYMENT_UNPAID]);

                throw ValidationException::withMessages([
                    'payment' => 'Имитация платежа завершилась ошибкой.',
                ]);
            }

            if ($effectivePaymentMethod !== 'wallet' && bccomp($chargeAmount, '0', 2) === 1) {
                $this->studentBalanceService->credit(
                    balance: $studentBalance,
                    amount: $chargeAmount,
                    currency: $currency,
                    type: StudentBalanceLedgerEntry::TYPE_TOPUP,
                    lesson: $lesson,
                    transaction: $transaction,
                    meta: [
                        'reason' => 'checkout_payment',
                        'package_code' => $lesson->package_code,
                        'wallet_contribution' => $walletContribution,
                    ],
                );
            }

            $this->studentBalanceService->debitForLesson(
                balance: $studentBalance->fresh(),
                amount: $payableAmount,
                currency: $currency,
                lesson: $lesson,
                transaction: $transaction,
                meta: [
                    'reason' => 'lesson_hold',
                    'package_code' => $lesson->package_code,
                    'wallet_contribution' => $walletContribution,
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
                'pending_amount' => $this->add((string) $balance->pending_amount, $netAmount),
            ]);

            $this->logFinancialOperation('payment_processed', [
                'lesson_id' => $lesson->id,
                'user_id' => $userId,
                'tutor_id' => $lesson->tutor_id,
                'amount' => $payableAmount,
                'charged_amount' => $chargeAmount,
                'wallet_contribution' => $walletContribution,
                'platform_commission' => $platformCommission,
                'acquiring_fee' => $acquiringFee,
                'net_amount' => $netAmount,
                'transaction_id' => $transaction->id,
            ]);

            $this->studentGoalService->ensureGoalForPaidLesson($lesson->fresh(['tutor.tutorProfile', 'student']));

            $lesson->student?->notify(new PaymentSucceededNotification($transaction));
            $lesson->tutor?->notify(new PaymentSucceededNotification($transaction));
            app(ChatService::class)->unlockContactsForLesson($lesson);

            return $transaction->fresh(['lesson', 'user']);
        });
    }

    public function refundLessonPayment(Lesson $lesson, ?string $reason = null): void
    {
        DB::transaction(function () use ($lesson, $reason): void {
            $lesson = Lesson::query()->with('transaction')->lockForUpdate()->findOrFail($lesson->id);

            if ($lesson->payment_status !== Lesson::PAYMENT_PAID || ! $lesson->transaction) {
                $lesson->update(['status' => Lesson::STATUS_CANCELLED]);

                return;
            }

            $transaction = $lesson->transaction;
            $gatewayTransactionId = $transaction->gateway_transaction_id ?? (string) $transaction->id;
            $chargedAmount = (string) ($transaction->gateway_response['charged_amount'] ?? $transaction->amount);
            $isWalletPayment = $transaction->payment_method === 'wallet';
            $refundToWallet = $isWalletPayment || bccomp($chargedAmount, (string) $transaction->amount, 2) === 1;

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
                    'refund_source' => $refundToWallet ? 'wallet' : 'gateway',
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

            $this->logFinancialOperation('payment_refunded', [
                'lesson_id' => $lesson->id,
                'transaction_id' => $transaction->id,
                'tutor_id' => $lesson->tutor_id,
                'net_amount' => (string) $transaction->net_amount,
                'reason' => $reason,
            ]);

            $lesson->student?->notify(new LessonCancelledNotification($lesson));
            $lesson->tutor?->notify(new LessonCancelledNotification($lesson));
        });
    }

    public function settleCompletedLesson(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson): void {
            $lesson = Lesson::query()->with('transaction')->lockForUpdate()->findOrFail($lesson->id);

            if ($lesson->status !== Lesson::STATUS_COMPLETED || $lesson->payment_status !== Lesson::PAYMENT_PAID || ! $lesson->transaction) {
                return;
            }

            $balance = TutorBalance::query()->firstOrCreate(
                ['user_id' => $lesson->tutor_id],
                [
                    'available_amount' => '0.00',
                    'pending_amount' => '0.00',
                    'total_earned' => '0.00',
                    'total_withdrawn' => '0.00',
                ],
            );

            $response = $lesson->transaction->gateway_response ?? [];

            if (($response['settled'] ?? false) === true) {
                return;
            }

            $studentBalance = $this->studentBalanceService->getOrCreate($lesson->transaction->user_id);
            $this->studentBalanceService->captureHeldForLesson(
                balance: $studentBalance,
                amount: (string) $lesson->transaction->amount,
                currency: (string) $lesson->transaction->currency,
                lesson: $lesson,
                transaction: $lesson->transaction,
                meta: [
                    'reason' => 'lesson_completed',
                ],
            );

            $balance->update([
                'pending_amount' => $this->maxZero($this->sub((string) $balance->pending_amount, (string) $lesson->transaction->net_amount)),
                'available_amount' => $this->add((string) $balance->available_amount, (string) $lesson->transaction->net_amount),
                'total_earned' => $this->add((string) $balance->total_earned, (string) $lesson->transaction->net_amount),
            ]);

            $lesson->transaction->update([
                'gateway_response' => array_merge($response, [
                    'settled' => true,
                    'settled_at' => now('UTC')->toISOString(),
                ]),
            ]);

            $this->logFinancialOperation('lesson_settled', [
                'lesson_id' => $lesson->id,
                'transaction_id' => $lesson->transaction->id,
                'tutor_id' => $lesson->tutor_id,
                'net_amount' => (string) $lesson->transaction->net_amount,
            ]);
        });
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
