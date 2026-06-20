<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\StudentBalance;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileStudentWalletHoldsCommand extends Command
{
    protected $signature = 'wallet:reconcile-holds';

    protected $description = 'Reconcile student locked balances for upcoming paid lessons.';

    public function handle(): int
    {
        $nowUtc = now('UTC');

        $transactions = Transaction::query()
            ->where('status', Transaction::STATUS_SUCCESS)
            ->get()
            ->filter(fn (Transaction $transaction): bool => ($transaction->gateway_response['settled'] ?? false) !== true);

        $expectedByUser = [];

        foreach ($transactions as $transaction) {
            $settlements = \App\Models\LessonSettlement::query()
                ->where('transaction_id', $transaction->id)
                ->get();

            $closedGross = '0.00';
            foreach ($settlements as $settlement) {
                $amount = $settlement->settled_at !== null
                    ? (string) $settlement->gross_share
                    : (string) ($settlement->meta['refunded_gross'] ?? '0.00');
                $closedGross = bcadd($closedGross, $amount, 2);
            }

            $remainingHold = bcsub((string) $transaction->amount, $closedGross, 2);
            if (bccomp($remainingHold, '0.00', 2) === 1) {
                $userId = $transaction->user_id;
                if (! isset($expectedByUser[$userId])) {
                    $expectedByUser[$userId] = '0.00';
                }
                $expectedByUser[$userId] = bcadd($expectedByUser[$userId], $remainingHold, 2);
            }
        }

        $updated = 0;

        DB::transaction(function () use ($expectedByUser, &$updated): void {
            foreach ($expectedByUser as $userId => $expectedLockedAmount) {
                $balance = StudentBalance::query()->firstOrCreate(
                    ['user_id' => (int) $userId],
                    [
                        'available_amount' => '0.00',
                        'locked_amount' => '0.00',
                        'total_topped_up' => '0.00',
                        'total_spent' => '0.00',
                        'total_refunded' => '0.00',
                    ],
                );

                if (bccomp((string) $balance->locked_amount, $expectedLockedAmount, 2) === -1) {
                    $balance->update([
                        'locked_amount' => $expectedLockedAmount,
                    ]);
                    $updated++;
                }
            }
        });

        $this->info("Reconciled student holds: {$updated} wallets updated.");

        return self::SUCCESS;
    }
}
