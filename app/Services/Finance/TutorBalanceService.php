<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\TutorBalance;
use Illuminate\Support\Facades\Log;

class TutorBalanceService
{
    public function __construct(
        private readonly LedgerClient $ledgerClient
    ) {}

    /**
     * Получить или создать баланс репетитора, синхронизировав его с Ledger.
     */
    public function getOrCreate(int $userId): TutorBalance
    {
        $balance = TutorBalance::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'ledger_wallet_id' => null,
                'available_amount' => '0.00',
                'pending_amount' => '0.00',
                'total_earned' => '0.00',
                'total_withdrawn' => '0.00',
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
                $this->syncBalance($balance);
            }
        }

        return $balance;
    }

    /**
     * Синхронизация балансов с Ledger микросервисом.
     */
    public function syncBalance(TutorBalance $balance): void
    {
        if (!$this->ledgerClient->isEnabled() || empty($balance->ledger_wallet_id)) {
            return;
        }

        try {
            $ledgerBalance = $this->ledgerClient->getBalance($balance->ledger_wallet_id);
            if ($ledgerBalance) {
                $balance->update([
                    'available_amount' => $ledgerBalance['actual_balance'], // Завершенные начисления — это доступно
                    'pending_amount' => $ledgerBalance['pending_balance'],  // Ожидающие начисления
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Сбой синхронизации баланса репетитора из Ledger', [
                'tutor_id' => $balance->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
