<?php

declare(strict_types=1);

namespace App\Contracts\Events;

/**
 * Единый JSON-конверт интеграционного события.
 *
 * Согласно требованию 8.1 и дизайну (раздел 3.4), конверт содержит:
 *  - `id`          — uuid (он же event_id, первичный ключ в outbox);
 *  - `type`        — строка формата `<aggregate>.<event>` (напр. `lesson.completed`);
 *  - `version`     — integer ≥ 1, мажорная версия схемы payload;
 *  - `occurred_at` — момент возникновения события в ISO-8601 UTC;
 *  - `tenant`      — идентификатор арендатора (по умолчанию `edusfera`);
 *  - `trace_id`    — request_id, проброшенный через всю цепочку (требование 5);
 *  - `actor`       — субъект (объект с `type`/`id`);
 *  - `aggregate`   — агрегат (объект с `type`/`id`);
 *  - `payload`     — тело события, валидируемое по JSON Schema (требование 8.3).
 *
 * DTO неизменяемый (readonly). Сериализация через `toArray()`/`jsonSerialize()`
 * производит snake_case-ключи, совпадающие с примером конверта из дизайна.
 */
final readonly class EventEnvelope implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $id,
        public string $type,
        public int $version,
        public string $occurredAt,
        public string $tenant,
        public string $traceId,
        public EventActor $actor,
        public EventAggregate $aggregate,
        public array $payload,
    ) {}

    /**
     * Полное имя event_type с версией, напр. `lesson.completed.v1`.
     * Используется для поиска JSON Schema и в столбце outbox.event_type.
     */
    public function eventType(): string
    {
        return $this->type.'.v'.$this->version;
    }

    /**
     * Сериализация конверта в массив со snake_case-ключами,
     * совпадающими с примером из дизайна (раздел 3.4).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'version' => $this->version,
            'occurred_at' => $this->occurredAt,
            'tenant' => $this->tenant,
            'trace_id' => $this->traceId,
            'actor' => $this->actor->toArray(),
            'aggregate' => $this->aggregate->toArray(),
            'payload' => $this->payload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
