<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Integrations\AiAssistantClient;
use App\Domain\Shared\Events\EventEnvelopeFactory;
use App\Integrations\Outbox\OutboxRepository;
use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Reconciliation: сверка состояния ядра с AI-сервисом (требование 15).
 *
 * Команда сравнивает множество завершённых уроков ядра (`status = completed`)
 * за период с множеством `lesson_id`, которые AI-сервис уже обработал
 * (через {@see AiAssistantClient}). Для расхождений — урок completed в ядре,
 * но AI его НЕ обработал — повторно публикует компенсирующее событие
 * `lesson.completed.v1` через outbox (требование 15.2).
 *
 * Идемпотентность (требование 15.4): каждый повторный republish защищён
 * окном 24 часа через ключ кеша `reconcile:lesson:{id}:{date}` (date = Y-m-d
 * текущего дня UTC). Повторный запуск в тот же день не публикует одно и то же
 * компенсирующее событие более одного раза. Используется store по умолчанию
 * (`config('cache.default')`) — в тестах это `array`, в prod — Redis/database.
 *
 * Каждое расхождение логируется в канал `api` с полями `lesson_id`,
 * `core_state`, `remote_state`, `action_taken` (требование 15.3).
 *
 * Запускается по расписанию `dailyAt('03:00')` (требование 15.5) через
 * `bootstrap/app.php->withSchedule`.
 */
class ReconcileLessonsWithAiCommand extends Command
{
    protected $signature = 'reconcile:lessons-with-ai
        {--since= : Начало периода (любой парсимый формат даты, UTC). По умолчанию now() - period дней.}
        {--period=7 : Размер окна сверки в днях, если --since не задан.}';

    protected $description = 'Сверяет завершённые уроки ядра с обработанными AI-сервисом и повторно публикует lesson.completed.v1 для расхождений';

    /**
     * TTL idempotency-окна в секундах (24 часа, требование 15.4).
     */
    private const IDEMPOTENCY_TTL_SECONDS = 86400;

    public function __construct(
        private readonly AiAssistantClient $aiAssistantClient,
        private readonly OutboxRepository $outboxRepository,
        private readonly EventEnvelopeFactory $envelopeFactory,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! Schema::hasTable('lessons')) {
            $this->warn('Таблица lessons еще не создана.');

            return self::SUCCESS;
        }

        [$from, $to] = $this->resolvePeriod();

        // Завершённые уроки ядра за период (по времени окончания урока).
        $lessons = Lesson::query()
            ->where('status', Lesson::STATUS_COMPLETED)
            ->whereBetween('end_time', [$from, $to])
            ->orderBy('id')
            ->get();

        // Набор lesson_id, которые AI-сервис уже обработал за тот же период.
        $processedIds = $this->aiAssistantClient->processedLessonIds($from, $to);
        $processedLookup = array_flip(array_map('intval', $processedIds));

        $today = Carbon::now('UTC')->format('Y-m-d');

        $discrepancies = 0;
        $republished = 0;
        $skipped = 0;

        foreach ($lessons as $lesson) {
            // Урок есть у AI — расхождения нет.
            if (isset($processedLookup[(int) $lesson->id])) {
                continue;
            }

            $discrepancies++;

            $cacheKey = sprintf('reconcile:lesson:%d:%s', $lesson->id, $today);

            // Idempotency-окно 24 часа: если уже republish'или сегодня — пропускаем.
            if (Cache::store(config('cache.default'))->has($cacheKey)) {
                $skipped++;

                continue;
            }

            // Republish одного компенсирующего события — в транзакции для
            // консистентности записи в outbox (требование 7.3/7.4).
            DB::transaction(function () use ($lesson): void {
                $this->outboxRepository->append(
                    $this->envelopeFactory->lessonCompleted($lesson)
                );
            });

            Cache::store(config('cache.default'))->put($cacheKey, true, self::IDEMPOTENCY_TTL_SECONDS);

            $republished++;

            // Требование 15.3: логируем каждое расхождение в канал `api`.
            Log::channel('api')->info('reconcile:lessons-with-ai discrepancy', [
                'lesson_id' => (int) $lesson->id,
                'core_state' => 'completed',
                'remote_state' => 'missing',
                'action_taken' => 'republished',
            ]);
        }

        Log::channel('api')->info('reconcile:lessons-with-ai completed', [
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'core_completed' => $lessons->count(),
            'discrepancies' => $discrepancies,
            'republished' => $republished,
            'skipped_idempotent' => $skipped,
        ]);

        $this->info(sprintf(
            'Reconciliation completed: %d completed lesson(s), %d discrepancy(ies), %d republished, %d skipped (idempotency window).',
            $lessons->count(),
            $discrepancies,
            $republished,
            $skipped,
        ));

        return self::SUCCESS;
    }

    /**
     * Определить период сверки `[from, to]` (UTC).
     *
     * `to` всегда now(); `from` берётся из `--since` (если задан) либо как
     * now() - `--period` дней (по умолчанию 7).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(): array
    {
        $to = Carbon::now('UTC');

        $since = $this->option('since');

        if (is_string($since) && $since !== '') {
            $from = Carbon::parse($since)->utc();
        } else {
            $period = (int) $this->option('period');
            $period = $period > 0 ? $period : 7;
            $from = $to->copy()->subDays($period);
        }

        return [$from, $to];
    }
}
