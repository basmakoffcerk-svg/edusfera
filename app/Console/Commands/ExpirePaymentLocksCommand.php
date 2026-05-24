<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ExpirePaymentLocksCommand extends Command
{
    protected $signature = 'lessons:expire-locks';

    protected $description = 'Отменяет уроки с истекшим payment lock (неоплаченные бронирования)';

    public function handle(): int
    {
        if (! Schema::hasTable('lessons')) {
            $this->warn('Таблица lessons еще не создана.');

            return self::SUCCESS;
        }

        $expired = Lesson::query()
            ->where('status', Lesson::STATUS_PENDING)
            ->where('payment_status', Lesson::PAYMENT_UNPAID)
            ->whereNotNull('payment_lock_expires_at')
            ->where('payment_lock_expires_at', '<', now('UTC'))
            ->get();

        $cancelled = 0;

        foreach ($expired as $lesson) {
            $lesson->update([
                'status' => Lesson::STATUS_CANCELLED,
            ]);
            $cancelled++;
        }

        $this->info("Отменено просроченных бронирований: {$cancelled}");

        return self::SUCCESS;
    }
}
