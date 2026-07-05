<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Lesson;
use App\Models\StudentBalance;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentBalanceService
{
    public function __construct(
        private readonly LedgerClient $ledgerClient
    ) {}

    /**
     * Получить или создать баланс, синхронизировав его с Ledger.
     */
    public function getOrCreate(int $userId): StudentBalance
    {
        $balance = StudentBalance::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'available_amount' => '0.00',
                'ledger_wallet_id' => null,
                'locked_amount' => '0.00',
                'total_topped_up' => '0.00',
                'total_spent' => '0.00',
                'total_refunded' => '0.00',
            ],
        );

        if ($this->ledgerClient->isEnabled()) {
            // 1. Ленивое создание кошелька в Ledger, если он еще не привязан
            if (empty($balance->ledger_wallet_id)) {
                $walletId = $this->ledgerClient->createWallet($userId);
                if ($walletId) {
                    $balance->update(['ledger_wallet_id' => $walletId]);
                }
            }

            // 2. Синхронизируем баланс с Ledger
            if ($balance->ledger_wallet_id) {
                $ledgerBalance = $this->ledgerClient->getBalance($balance->ledger_wallet_id);
                if ($ledgerBalance) {
                    $balance->update([
                        'available_amount' => $ledgerBalance['available_balance'],
                        'locked_amount' => $ledgerBalance['locked_balance'],
                    ]);
                }
            }
        }

        return $balance;
    }

    /**
     * Пополнение баланса (депозит).
     */
    public function credit(
        StudentBalance $balance,
        string $amount,
        string $currency,
        string $type,
        ?Lesson $lesson = null,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        $ledgerTxId = null;
        $skipLedger = $meta['skip_ledger'] ?? false;

        if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
            $ledgerTx = $this->ledgerClient->createTransaction(
                walletId: $balance->ledger_wallet_id,
                amount: $amount,
                type: 'deposit',
                status: 'completed',
                externalId: $transaction?->gateway_transaction_id
            );

            if ($ledgerTx) {
                $ledgerTxId = $ledgerTx['id'];
            } else {
                throw new \RuntimeException('Ledger transaction creation failed');
            }
        }

        return DB::transaction(function () use ($balance, $amount, $type, $currency, $lesson, $transaction, $meta, $ledgerTxId, $skipLedger) {
            // Обновляем локальный баланс (fallback / локальное кэширование)
            $balance->update([
                'available_amount' => $this->add((string) $balance->available_amount, $amount),
                'total_topped_up' => $type === StudentBalanceLedgerEntry::TYPE_TOPUP
                    ? $this->add((string) $balance->total_topped_up, $amount)
                    : (string) $balance->total_topped_up,
                'total_refunded' => $type === StudentBalanceLedgerEntry::TYPE_REFUND
                    ? $this->add((string) $balance->total_refunded, $amount)
                    : (string) $balance->total_refunded,
            ]);

            // Сохраняем запись в локальном логе
            $mergedMeta = array_merge($meta ?? [], [
                'ledger_transaction_id' => $ledgerTxId,
            ]);

            StudentBalanceLedgerEntry::query()->create([
                'student_balance_id' => $balance->id,
                'user_id' => $balance->user_id,
                'lesson_id' => $lesson?->id,
                'transaction_id' => $transaction?->id,
                'type' => $type,
                'amount' => $amount,
                'currency' => $currency,
                'meta' => $mergedMeta,
            ]);

            // Финальная синхронизация балансов, если Ledger вернул ответ
            if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
                $this->syncBalance($balance);
            }

            return $balance->fresh();
        });
    }

    /**
     * Создание блокировки (Hold) для урока.
     */
    public function debitForLesson(
        StudentBalance $balance,
        string $amount,
        string $currency,
        Lesson $lesson,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        $ledgerTxId = null;
        $skipLedger = $meta['skip_ledger'] ?? false;

        if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
            try {
                // В Ledger списания идут со знаком минус
                $negativeAmount = $this->sub('0.00', $amount);

                $ledgerTx = $this->ledgerClient->createTransaction(
                    walletId: $balance->ledger_wallet_id,
                    amount: $negativeAmount,
                    type: 'hold',
                    status: 'pending'
                );

                if ($ledgerTx) {
                    $ledgerTxId = $ledgerTx['id'];
                } else {
                    throw new \RuntimeException('Ledger transaction creation failed');
                }
            } catch (\RuntimeException $e) {
                if ($e->getMessage() === 'insufficient_funds') {
                    throw ValidationException::withMessages([
                        'payment' => 'Недостаточно средств на внутреннем балансе (проверено Ledger).',
                    ]);
                }
                throw $e;
            }
        } else {
            // Локальная проверка при выключенном Ledger или при skip_ledger
            if (bccomp((string) $balance->available_amount, $amount, 2) === -1) {
                throw ValidationException::withMessages([
                    'payment' => 'Недостаточно средств на внутреннем балансе.',
                ]);
            }
        }

        return DB::transaction(function () use ($balance, $amount, $currency, $lesson, $transaction, $meta, $ledgerTxId, $skipLedger) {
            // Обновляем локальный баланс
            $balance->update([
                'available_amount' => $this->sub((string) $balance->available_amount, $amount),
                'locked_amount' => $this->add((string) $balance->locked_amount, $amount),
            ]);

            $mergedMeta = array_merge($meta ?? [], [
                'ledger_transaction_id' => $ledgerTxId,
            ]);

            StudentBalanceLedgerEntry::query()->create([
                'student_balance_id' => $balance->id,
                'user_id' => $balance->user_id,
                'lesson_id' => $lesson->id,
                'transaction_id' => $transaction?->id,
                'type' => StudentBalanceLedgerEntry::TYPE_HOLD,
                'amount' => $amount,
                'currency' => $currency,
                'meta' => $mergedMeta,
            ]);

            if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
                $this->syncBalance($balance);
            }

            return $balance->fresh();
        });
    }

    /**
     * Отмена блокировки (Void Hold).
     */
    public function releaseHeldForLesson(
        StudentBalance $balance,
        string $amount,
        string $currency,
        Lesson $lesson,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        $skipLedger = $meta['skip_ledger'] ?? false;

        if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
            $holdEntry = StudentBalanceLedgerEntry::query()
                ->where('student_balance_id', $balance->id)
                ->where('lesson_id', $lesson->id)
                ->where('type', StudentBalanceLedgerEntry::TYPE_HOLD)
                ->latest()
                ->first();

            $ledgerTxId = $holdEntry?->meta['ledger_transaction_id'] ?? null;
            if ($ledgerTxId) {
                $res = $this->ledgerClient->updateTransactionStatus($ledgerTxId, 'failed');
                if (!$res) {
                    throw new \RuntimeException('Ledger transaction status update failed');
                }
            }
        }

        return DB::transaction(function () use ($balance, $amount, $currency, $lesson, $transaction, $meta, $skipLedger) {
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

            if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
                $this->syncBalance($balance);
            }

            return $balance->fresh();
        });
    }

    /**
     * Списание блокировки (Settle Hold).
     */
    public function captureHeldForLesson(
        StudentBalance $balance,
        string $amount,
        string $currency,
        Lesson $lesson,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        $skipLedger = $meta['skip_ledger'] ?? false;

        if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
            $holdEntry = StudentBalanceLedgerEntry::query()
                ->where('student_balance_id', $balance->id)
                ->where('lesson_id', $lesson->id)
                ->where('type', StudentBalanceLedgerEntry::TYPE_HOLD)
                ->latest()
                ->first();

            $ledgerTxId = $holdEntry?->meta['ledger_transaction_id'] ?? null;
            if ($ledgerTxId) {
                $res = $this->ledgerClient->updateTransactionStatus($ledgerTxId, 'completed');
                if (!$res) {
                    throw new \RuntimeException('Ledger transaction status update failed');
                }
            }
        }

        return DB::transaction(function () use ($balance, $amount, $currency, $lesson, $transaction, $meta, $skipLedger) {
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

            if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
                $this->syncBalance($balance);
            }

            return $balance->fresh();
        });
    }

    /**
     * Возврат списанных средств.
     */
    public function refundHeldExternally(
        StudentBalance $balance,
        string $amount,
        string $currency,
        Lesson $lesson,
        ?Transaction $transaction = null,
        ?array $meta = null,
    ): StudentBalance {
        $ledgerTxId = null;
        $skipLedger = $meta['skip_ledger'] ?? false;

        if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
            $ledgerTx = $this->ledgerClient->createTransaction(
                walletId: $balance->ledger_wallet_id,
                amount: $amount,
                type: 'deposit',
                status: 'completed',
                externalId: $transaction?->gateway_transaction_id
            );

            if ($ledgerTx) {
                $ledgerTxId = $ledgerTx['id'];
            } else {
                throw new \RuntimeException('Ledger transaction creation failed');
            }
        }

        return DB::transaction(function () use ($balance, $amount, $currency, $lesson, $transaction, $meta, $ledgerTxId, $skipLedger) {
            $balance->update([
                'locked_amount' => $this->maxZero($this->sub((string) $balance->locked_amount, $amount)),
                'total_refunded' => $this->add((string) $balance->total_refunded, $amount),
            ]);

            $mergedMeta = array_merge($meta ?? [], [
                'ledger_transaction_id' => $ledgerTxId,
            ]);

            StudentBalanceLedgerEntry::query()->create([
                'student_balance_id' => $balance->id,
                'user_id' => $balance->user_id,
                'lesson_id' => $lesson->id,
                'transaction_id' => $transaction?->id,
                'type' => StudentBalanceLedgerEntry::TYPE_REFUND,
                'amount' => $amount,
                'currency' => $currency,
                'meta' => $mergedMeta,
            ]);

            if ($this->ledgerClient->isEnabled() && $balance->ledger_wallet_id && !$skipLedger) {
                $this->syncBalance($balance);
            }

            return $balance->fresh();
        });
    }

    /**
     * Вспомогательный метод синхронизации балансов.
     */
    public function syncBalance(StudentBalance $balance): void
    {
        try {
            $ledgerBalance = $this->ledgerClient->getBalance($balance->ledger_wallet_id);
            if ($ledgerBalance) {
                $balance->update([
                    'available_amount' => $ledgerBalance['available_balance'],
                    'locked_amount' => $ledgerBalance['locked_balance'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Сбой синхронизации баланса из Ledger', [
                'wallet_id' => $balance->ledger_wallet_id,
                'error' => $e->getMessage(),
            ]);
        }
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
