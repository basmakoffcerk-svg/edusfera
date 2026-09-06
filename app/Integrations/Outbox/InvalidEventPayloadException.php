<?php

declare(strict_types=1);

namespace App\Integrations\Outbox;

use RuntimeException;

/**
 * Бросается, когда payload интеграционного события не соответствует
 * соответствующей JSON Schema (`docs/events/{event-type}.v{N}.json`).
 *
 * Согласно требованию 8.4, при невалидном payload запись в `integration_outbox`
 * НЕ вставляется — исключение прерывает окружающую транзакцию вызывающего кода.
 */
final class InvalidEventPayloadException extends RuntimeException
{
    /**
     * @param  list<string>  $errors  человекочитаемые ошибки валидации схемы
     */
    public function __construct(
        public readonly string $eventType,
        public readonly array $errors = [],
        string $message = '',
    ) {
        parent::__construct(
            $message !== ''
                ? $message
                : sprintf(
                    'Payload for event "%s" failed JSON Schema validation: %s',
                    $eventType,
                    implode('; ', $errors) !== '' ? implode('; ', $errors) : 'unknown error',
                ),
        );
    }

    /**
     * Удобный конструктор для случая «схема не найдена».
     */
    public static function schemaNotFound(string $eventType, string $schemaPath): self
    {
        return new self(
            $eventType,
            ["schema file not found: {$schemaPath}"],
            sprintf('JSON Schema for event "%s" not found at %s', $eventType, $schemaPath),
        );
    }
}
