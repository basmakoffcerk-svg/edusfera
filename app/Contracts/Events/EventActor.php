<?php

declare(strict_types=1);

namespace App\Contracts\Events;

/**
 * Субъект (actor), инициировавший интеграционное событие.
 *
 * Согласно требованию 8.1, поле `actor` конверта — объект с обязательными
 * `type` и `id`. Опциональный `role` сохраняется для совместимости с примером
 * конверта из дизайна (раздел 3.4), где у actor'а присутствует роль.
 */
final readonly class EventActor implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string|int $id,
        public ?string $role = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'id' => $this->id,
        ];

        if ($this->role !== null) {
            $data['role'] = $this->role;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
