<?php

declare(strict_types=1);

namespace App\Integrations\EventBus;

use App\Contracts\Events\EventBusInterface;
use App\Contracts\Events\EventEnvelope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Реализация EventBusInterface поверх Redis Streams (XADD).
 *
 * Согласно требованию 9.2, выполняет:
 *   XADD topic:{aggregate_type} * payload <json-envelope>
 *
 * Ключ стрима: `topic:{aggregate_type}` (напр. `topic:lesson`).
 * Поле `payload` содержит полный JSON-конверт (`$envelope->toArray()`).
 *
 * Ошибки Redis обрабатываются gracefully: исключение логируется в канал `api`
 * и пробрасывается дальше, чтобы вызывающий код (outbox publisher) мог
 * зафиксировать неудачу и увеличить счётчик попыток (требование 9.7).
 */
final class RedisStreamsEventBus implements EventBusInterface
{
    /**
     * Префикс ключа стрима. Итоговый ключ: `{streamPrefix}{aggregate_type}`.
     */
    private string $streamPrefix;

    public function __construct(string $streamPrefix = 'topic:')
    {
        $this->streamPrefix = $streamPrefix;
    }

    public function publish(EventEnvelope $envelope): void
    {
        $stream = $this->streamPrefix.$envelope->aggregate->type;

        $payload = json_encode(
            $envelope->toArray(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        try {
            Redis::xAdd($stream, '*', ['payload' => $payload]);
        } catch (Throwable $e) {
            Log::channel('api')->error('RedisStreamsEventBus: failed to publish event', [
                'event_id' => $envelope->id,
                'event_type' => $envelope->eventType(),
                'stream' => $stream,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
