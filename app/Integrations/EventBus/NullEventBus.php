<?php

declare(strict_types=1);

namespace App\Integrations\EventBus;

use App\Contracts\Events\EventBusInterface;
use App\Contracts\Events\EventEnvelope;
use Illuminate\Support\Facades\Log;

/**
 * No-op реализация EventBusInterface для тестов и локальной разработки.
 *
 * Согласно требованию 9.3, не выполняет никаких сетевых операций.
 * Опционально логирует факт публикации в канал `api` с уровнем debug,
 * что позволяет убедиться в корректности вызовов без реального брокера.
 */
final class NullEventBus implements EventBusInterface
{
    public function __construct(
        private readonly bool $logEnabled = false,
    ) {}

    public function publish(EventEnvelope $envelope): void
    {
        if ($this->logEnabled) {
            Log::channel('api')->debug('NullEventBus: event discarded', [
                'event_id' => $envelope->id,
                'event_type' => $envelope->eventType(),
                'aggregate_type' => $envelope->aggregate->type,
                'aggregate_id' => $envelope->aggregate->id,
            ]);
        }
    }
}
