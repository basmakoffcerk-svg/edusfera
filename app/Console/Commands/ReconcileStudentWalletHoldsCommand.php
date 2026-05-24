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

        $expectedByUser = Transaction::query()
            ->select(['user_id', 'amount', 'gateway_response'])
            ->where('status', Transaction::STATUS_SUCCESS)
            ->whereHas('lesson', function ($query) use ($nowUtc): void {
                $query
                    ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])
                    ->where('payment_status', Lesson::PAYMENT_PAID)
                    ->where('end_time', '>', $nowUtc);
            })
            ->get()
            ->filter(fn (Transaction $transaction): bool => ($transaction->gateway_response['settled'] ?? false) !== true)
            ->groupBy('user_id')
            ->map(fn ($transactions): string => number_format(
                (float) $transactions->sum(fn (Transaction $transaction): float => (float) $transaction->amount),
                2,
                '.',
                '',
            ));

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
