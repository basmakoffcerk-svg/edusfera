<?php

declare(strict_types=1);

namespace App\Contracts\Events;

/**
 * Абстракция транспорта интеграционных событий.
 *
 * Согласно требованию 9.1, интерфейс определяет единственный метод `publish`,
 * принимающий готовый конверт. Конкретная реализация (Redis Streams, RabbitMQ,
 * NATS, Null) подставляется через IoC-контейнер на основе
 * `config('events.bus.driver')` (требование 9.4).
 */
interface EventBusInterface
{
    /**
     * Опубликовать интеграционное событие в транспорт.
     *
     * Реализация ДОЛЖНА быть идемпотентной по `$envelope->id` на стороне
     * consumer'а (at-least-once гарантия, dedup — ответственность consumer'а).
     *
     * @throws \RuntimeException если транспорт недоступен и реализация не
     *                           подавляет ошибки (NullEventBus — подавляет).
     */
    public function publish(EventEnvelope $envelope): void;
}
