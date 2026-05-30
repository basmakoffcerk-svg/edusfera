<?php

declare(strict_types=1);

namespace App\Contracts\Events;

/**
 * Агрегат, к которому относится интеграционное событие.
 *
 * Согласно требованию 8.1, поле `aggregate` конверта — объект с обязательными
 * `type` и `id`. `type` используется как ключ партиционирования в брокере
 * (см. требование 9.2: `topic:{aggregate_type}`).
 */
final readonly class EventAggregate implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string|int $id,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
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
