<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\Events\EventBusInterface;
use App\Contracts\Events\EventEnvelope;

/**
 * In-memory реализация {@see EventBusInterface} для property-based тестов outbox.
 *
 * Накапливает факты публикаций:
 *  - `counts`     — карта `event_id => число вызовов publish()` (для проверки
 *    at-least-once и идемпотентности повторного запуска publisher'а);
 *  - `deliveries` — плоский упорядоченный список `event_id` в порядке доставки
 *    (используется как источник для симуляции at-least-once redelivery и
 *    проверки дедупа на стороне consumer'а).
 *
 * Один экземпляр переиспользуется между итерациями property-теста: состояние
 * сбрасывается через {@see reset()}, чтобы команда `integration:publish-outbox`
 * (резолвящая шину из контейнера) всегда писала в один и тот же объект.
 */
final class RecordingEventBus implements EventBusInterface
{
    /** @var array<string, int> event_id => число вызовов publish() */
    private array $counts = [];

    /** @var list<string> event_id в порядке доставки (с возможными дублями) */
    private array $deliveries = [];

    public function publish(EventEnvelope $envelope): void
    {
        $id = $envelope->id;

        $this->counts[$id] = ($this->counts[$id] ?? 0) + 1;
        $this->deliveries[] = $id;
    }

    /**
     * Сбросить накопленное состояние (между итерациями property-теста).
     */
    public function reset(): void
    {
        $this->counts = [];
        $this->deliveries = [];
    }

    /**
     * Сколько раз publisher доставил событие с данным event_id.
     */
    public function countFor(string $eventId): int
    {
        return $this->counts[$eventId] ?? 0;
    }

    /**
     * Было ли событие доставлено хотя бы раз.
     */
    public function has(string $eventId): bool
    {
        return isset($this->counts[$eventId]);
    }

    /**
     * Суммарное число доставок по всем event_id.
     */
    public function totalDeliveries(): int
    {
        return array_sum($this->counts);
    }

    /**
     * @return list<string> event_id в порядке доставки (возможны дубли)
     */
    public function deliveries(): array
    {
        return $this->deliveries;
    }

    /**
     * @return array<string, int> event_id => число доставок
     */
    public function counts(): array
    {
        return $this->counts;
    }
}
