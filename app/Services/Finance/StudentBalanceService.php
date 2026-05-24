<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Lesson;
use App\Models\StudentBalance;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\Transaction;
use Illuminate\Validation\ValidationException;

class StudentBalanceService
{
    public function getOrCreate(int $userId): StudentBalance
    {
        return StudentBalance::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'available_amount' => '0.00',
                'locked_amount' => '0.00',
                'total_topped_up' => '0.00',
                'total_spent' => '0.00',
                'total_refunded' => '0.00',
            ],
        );
    }

    public function credit(
        StudentBalance $balance,
        string $amount,
        string $currency,
        string $type,
        ?Lesson $lesson = null,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        $balance->update([
            'available_amount' => $this->add((string) $balance->available_amount, $amount),
            'total_topped_up' => $type === StudentBalanceLedgerEntry::TYPE_TOPUP
                ? $this->add((string) $balance->total_topped_up, $amount)
                : (string) $balance->total_topped_up,
            'total_refunded' => $type === StudentBalanceLedgerEntry::TYPE_REFUND
                ? $this->add((string) $balance->total_refunded, $amount)
                : (string) $balance->total_refunded,
        ]);

        StudentBalanceLedgerEntry::query()->create([
            'student_balance_id' => $balance->id,
            'user_id' => $balance->user_id,
            'lesson_id' => $lesson?->id,
            'transaction_id' => $transaction?->id,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'meta' => $meta,
        ]);

        return $balance->fresh();
    }

    public function debitForLesson(
        StudentBalance $balance,
        string $amount,
        string $currency,
        Lesson $lesson,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        if (bccomp((string) $balance->available_amount, $amount, 2) === -1) {
            throw ValidationException::withMessages([
                'payment' => 'Недостаточно средств на внутреннем балансе.',
            ]);
        }

        $balance->update([
            'available_amount' => $this->sub((string) $balance->available_amount, $amount),
            'locked_amount' => $this->add((string) $balance->locked_amount, $amount),
        ]);

        StudentBalanceLedgerEntry::query()->create([
            'student_balance_id' => $balance->id,
            'user_id' => $balance->user_id,
            'lesson_id' => $lesson->id,
            'transaction_id' => $transaction?->id,
            'type' => StudentBalanceLedgerEntry::TYPE_HOLD,
            'amount' => $amount,
            'currency' => $currency,
            'meta' => $meta,
        ]);

        return $balance->fresh();
    }

    public function releaseHeldForLesson(
        StudentBalance $balance,
        string $amount,
        string $currency,
        Lesson $lesson,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        $balance->update([
            'locked_amount' => $this->maxZero($this->sub((string) $balance->locked_amount, $amount)),
            'available_amount' => $this->add((string) $balance->available_amount, $amount),
        ]);

        StudentBalanceLedgerEntry::query()->create([
            'student_balance_id' => $balance->id,
            'user_id' => $balance->user_id,
            'lesson_id' => $lesson->id,
            'transaction_id' => $transaction?->id,
            'type' => StudentBalanceLedgerEntry::TYPE_RELEASE,
            'amount' => $amount,
            'currency' => $currency,
            'meta' => $meta,
        ]);

        return $balance->fresh();
    }

    public function captureHeldForLesson(
        StudentBalance $balance,
        string $amount,
        string $currency,
        Lesson $lesson,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        $balance->update([
            'locked_amount' => $this->maxZero($this->sub((string) $balance->locked_amount, $amount)),
            'total_spent' => $this->add((string) $balance->total_spent, $amount),
        ]);

        StudentBalanceLedgerEntry::query()->create([
            'student_balance_id' => $balance->id,
            'user_id' => $balance->user_id,
            'lesson_id' => $lesson->id,
            'transaction_id' => $transaction?->id,
            'type' => StudentBalanceLedgerEntry::TYPE_PAYMENT,
            'amount' => $amount,
            'currency' => $currency,
            'meta' => $meta,
        ]);

        return $balance->fresh();
    }

    public function refundHeldExternally(
        StudentBalance $balance,
        string $amount,
        string $currency,
        Lesson $lesson,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        $balance->update([
            'locked_amount' => $this->maxZero($this->sub((string) $balance->locked_amount, $amount)),
            'total_refunded' => $this->add((string) $balance->total_refunded, $amount),
        ]);

        StudentBalanceLedgerEntry::query()->create([
            'student_balance_id' => $balance->id,
            'user_id' => $balance->user_id,
            'lesson_id' => $lesson->id,
            'transaction_id' => $transaction?->id,
            'type' => StudentBalanceLedgerEntry::TYPE_REFUND,
            'amount' => $amount,
            'currency' => $currency,
            'meta' => $meta,
        ]);

        return $balance->fresh();
    }

    private function add(string $left, string $right): string
    {
        return bcadd($left, $right, 2);
    }

    private function sub(string $left, string $right): string
    {
        return bcsub($left, $right, 2);
    }

    private function maxZero(string $amount): string
    {
        return bccomp($amount, '0', 2) === -1 ? '0.00' : $amount;
    }
}
