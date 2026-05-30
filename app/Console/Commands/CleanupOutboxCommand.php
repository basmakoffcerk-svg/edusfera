<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Outbox Cleanup — обслуживающая cron-команда, удаляющая старые опубликованные
 * строки из таблицы `integration_outbox`.
 *
 * Согласно требованию 7.6:
 *  - Удаляются строки, у которых `published_at IS NOT NULL` И
 *    `published_at < now() - retention_days` (по умолчанию 30 дней).
 *  - Неопубликованные строки (`published_at IS NULL`) НЕ удаляются никогда,
 *    даже если они старые по `created_at` — они ещё ждут публикации.
 *  - Недавно опубликованные строки (`published_at >= now() - retention_days`)
 *    сохраняются для аудита и возможного повторного анализа.
 *
 * Удаление выполняется пачками по 1000 строк, чтобы не держать длинную
 * транзакцию и не раздувать WAL на больших таблицах. Поскольку PostgreSQL
 * не поддерживает `DELETE ... LIMIT` напрямую, ограничение реализовано через
 * подзапрос по первичному ключу `id` — это работает и на PostgreSQL, и на
 * SQLite (тесты).
 *
 * Запускается по расписанию `dailyAt('03:30')` через `bootstrap/app.php->withSchedule`.
 */
class CleanupOutboxCommand extends Command
{
    protected $signature = 'integration:cleanup-outbox';

    protected $description = 'Удаляет старые опубликованные строки integration_outbox (published_at старше retention_days)';

    /**
     * Размер пачки удаления. Удаляем порциями, чтобы избежать длинных
     * блокировок и большого роста WAL на PostgreSQL.
     */
    private const BATCH_SIZE = 1000;

    public function handle(): int
    {
        $retentionDays = (int) config('events.outbox.retention_days', 30);
        $threshold = Carbon::now('UTC')->subDays($retentionDays)->format('Y-m-d H:i:s');

        $totalDeleted = 0;

        do {
            // Подзапрос по id для совместимости с PostgreSQL (нет DELETE ... LIMIT).
            // На SQLite/MySQL это тоже корректно работает.
            $deleted = DB::table('integration_outbox')
                ->whereIn('id', function ($query) use ($threshold): void {
                    $query->select('id')
                        ->from('integration_outbox')
                        ->whereNotNull('published_at')
                        ->where('published_at', '<', $threshold)
                        ->limit(self::BATCH_SIZE);
                })
                ->delete();

            $totalDeleted += $deleted;
        } while ($deleted > 0);

        Log::channel('api')->info('integration:cleanup-outbox completed', [
            'deleted' => $totalDeleted,
            'retention_days' => $retentionDays,
            'threshold' => $threshold,
        ]);

        $this->info("Cleaned up {$totalDeleted} published outbox row(s) older than {$retentionDays} day(s).");

        return self::SUCCESS;
    }
}
