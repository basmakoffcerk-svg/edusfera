<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Events\EventActor;
use App\Contracts\Events\EventAggregate;
use App\Contracts\Events\EventBusInterface;
use App\Contracts\Events\EventEnvelope;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Outbox Publisher — воркер, публикующий неотправленные интеграционные события.
 *
 * Согласно требованиям 9.5–9.8:
 *  - Берёт пачку 100 строк с `published_at IS NULL AND available_at <= now()`
 *    под `FOR UPDATE SKIP LOCKED` (PostgreSQL-совместимо).
 *  - Для каждой строки вызывает `EventBusInterface::publish($envelope)`.
 *  - На успехе: `published_at = now()`, `last_error = NULL`.
 *  - На ошибке: `attempts++`, `last_error = <message>`, `published_at` остаётся NULL.
 *  - При `attempts >= 10`: exponential backoff `available_at = now() + min(2^attempts, 3600) сек`.
 *
 * Запускается по расписанию `everyMinute()` через `bootstrap/app.php->withSchedule`.
 */
class PublishOutboxCommand extends Command
{
    protected $signature = 'integration:publish-outbox';

    protected $description = 'Публикует неотправленные строки integration_outbox в EventBus';

    public function __construct(private readonly EventBusInterface $eventBus)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = Carbon::now('UTC');

        // Выбираем пачку 100 неопубликованных строк, доступных для обработки.
        // FOR UPDATE SKIP LOCKED — PostgreSQL-совместимый способ избежать
        // конкурентной обработки одних и тех же строк несколькими воркерами.
        $rows = DB::table('integration_outbox')
            ->whereNull('published_at')
            ->where('available_at', '<=', $now)
            ->orderBy('available_at')
            ->limit(100)
            ->lockForUpdate()
            ->get();

        $published = 0;
        $failed = 0;

        foreach ($rows as $row) {
            try {
                $envelope = $this->buildEnvelope($row);
                $this->eventBus->publish($envelope);

                DB::table('integration_outbox')
                    ->where('id', $row->id)
                    ->update([
                        'published_at' => Carbon::now('UTC')->format('Y-m-d H:i:s'),
                        'last_error' => null,
                        'updated_at' => Carbon::now('UTC')->format('Y-m-d H:i:s'),
                    ]);

                $published++;
            } catch (Throwable $e) {
                $newAttempts = (int) $row->attempts + 1;
                $update = [
                    'attempts' => $newAttempts,
                    'last_error' => mb_substr($e->getMessage(), 0, 65535),
                    'updated_at' => Carbon::now('UTC')->format('Y-m-d H:i:s'),
                ];

                // Exponential backoff применяется только при attempts >= 10
                // (до этого — стандартный retry на следующем тике).
                if ($newAttempts >= 10) {
                    $backoffSeconds = min(2 ** $newAttempts, 3600);
                    $update['available_at'] = Carbon::now('UTC')
                        ->addSeconds($backoffSeconds)
                        ->format('Y-m-d H:i:s');
                }

                DB::table('integration_outbox')
                    ->where('id', $row->id)
                    ->update($update);

                $failed++;
            }
        }

        Log::channel('api')->info('integration:publish-outbox completed', [
            'published' => $published,
            'failed' => $failed,
            'total' => $published + $failed,
        ]);

        return self::SUCCESS;
    }

    /**
     * Восстановить EventEnvelope из JSON-payload строки outbox.
     *
     * Поле `payload` хранит полный сериализованный конверт (требование 7.5).
     *
     * @param  object  $row  строка из `integration_outbox`
     *
     * @throws \JsonException если payload не является валидным JSON
     * @throws \RuntimeException если структура payload некорректна
     */
    private function buildEnvelope(object $row): EventEnvelope
    {
        $data = json_decode((string) $row->payload, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new \RuntimeException("Invalid payload for outbox row {$row->id}: not an array");
        }

        $actor = isset($data['actor']) && is_array($data['actor'])
            ? new EventActor(
                type: (string) ($data['actor']['type'] ?? 'system'),
                id: $data['actor']['id'] ?? 0,
                role: isset($data['actor']['role']) ? (string) $data['actor']['role'] : null,
            )
            : new EventActor(type: 'system', id: 0);

        $aggregate = isset($data['aggregate']) && is_array($data['aggregate'])
            ? new EventAggregate(
                type: (string) ($data['aggregate']['type'] ?? ''),
                id: $data['aggregate']['id'] ?? '',
            )
            : new EventAggregate(type: '', id: '');

        return new EventEnvelope(
            id: (string) ($data['id'] ?? $row->id),
            type: (string) ($data['type'] ?? $row->event_type),
            version: (int) ($data['version'] ?? 1),
            occurredAt: (string) ($data['occurred_at'] ?? $row->occurred_at),
            tenant: (string) ($data['tenant'] ?? 'edusfera'),
            traceId: (string) ($data['trace_id'] ?? ''),
            actor: $actor,
            aggregate: $aggregate,
            payload: is_array($data['payload'] ?? null) ? $data['payload'] : [],
        );
    }
}
