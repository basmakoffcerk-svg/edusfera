<?php

declare(strict_types=1);

namespace App\Integrations\Outbox;

use App\Contracts\Events\EventEnvelope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

/**
 * Репозиторий integration outbox.
 *
 * `append()` валидирует payload конверта по соответствующей JSON Schema
 * (`docs/events/{event-type}.json`) и вставляет строку в `integration_outbox`.
 *
 * ВАЖНО (требование 7.3): метод НЕ открывает собственную `DB::transaction`.
 * Он выполняет один INSERT, чтобы участвовать в окружающей транзакции
 * вызывающего доменного сервиса — так запись в outbox и доменное изменение
 * фиксируются атомарно (требование 7.4).
 *
 * При несоответствии payload схеме бросается InvalidEventPayloadException и
 * запись НЕ вставляется (требование 8.4).
 */
final class OutboxRepository
{
    public const TABLE = 'integration_outbox';

    /**
     * Директория с JSON Schema интеграционных событий.
     */
    private string $schemaDir;

    public function __construct(?string $schemaDir = null)
    {
        $this->schemaDir = $schemaDir ?? base_path('docs/events');
    }

    /**
     * Провалидировать payload по JSON Schema и вставить конверт в outbox.
     *
     * @throws InvalidEventPayloadException если payload не соответствует схеме
     */
    public function append(EventEnvelope $e): void
    {
        $this->validatePayload($e);

        $now = Carbon::now('UTC');

        DB::table(self::TABLE)->insert([
            'id' => $e->id,
            'event_type' => $e->eventType(),
            'aggregate_type' => $e->aggregate->type,
            'aggregate_id' => (string) $e->aggregate->id,
            // payload-столбец хранит ВЕСЬ сериализованный envelope (требование 7.5).
            'payload' => json_encode($e->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'occurred_at' => $this->toTimestamp($e->occurredAt),
            'available_at' => $now->format('Y-m-d H:i:s'),
            'published_at' => null,
            'attempts' => 0,
            'last_error' => null,
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Провалидировать payload конверта по JSON Schema события.
     *
     * @throws InvalidEventPayloadException
     */
    private function validatePayload(EventEnvelope $e): void
    {
        $schemaPath = $this->schemaPath($e);

        if (! is_file($schemaPath)) {
            throw InvalidEventPayloadException::schemaNotFound($e->eventType(), $schemaPath);
        }

        $schema = json_decode((string) file_get_contents($schemaPath));

        if (! $schema instanceof \stdClass) {
            throw new InvalidEventPayloadException(
                $e->eventType(),
                ['schema file is not a valid JSON object'],
            );
        }

        // justinrainbow валидирует stdClass-объекты, а не ассоциативные массивы.
        // Преобразуем payload в объектную форму. Пустой payload -> пустой объект,
        // иначе json_encode([]) даст "[]" и тип "object" не пройдёт.
        $data = $e->payload === []
            ? new \stdClass
            : json_decode(json_encode($e->payload, JSON_THROW_ON_ERROR));

        $validator = new Validator;
        // Без CHECK_MODE_TYPE_CAST: типы проверяются строго (требование 8.5 —
        // поле не должно менять тип), строка "1" не считается integer.
        $validator->validate($data, $schema, Constraint::CHECK_MODE_NORMAL);

        if (! $validator->isValid()) {
            throw new InvalidEventPayloadException(
                $e->eventType(),
                $this->formatErrors($validator->getErrors()),
            );
        }
    }

    private function schemaPath(EventEnvelope $e): string
    {
        return rtrim($this->schemaDir, '/').'/'.$e->eventType().'.json';
    }

    /**
     * @param  array<int, array<string, mixed>>  $errors
     * @return list<string>
     */
    private function formatErrors(array $errors): array
    {
        $messages = [];

        foreach ($errors as $error) {
            $property = isset($error['property']) && $error['property'] !== ''
                ? $error['property']
                : '(root)';
            $message = $error['message'] ?? 'invalid';

            $messages[] = sprintf('%s: %s', $property, $message);
        }

        return $messages === [] ? ['payload does not match schema'] : $messages;
    }

    /**
     * Нормализовать ISO-8601 строку occurred_at в формат timestamp для БД (UTC).
     */
    private function toTimestamp(string $iso8601): string
    {
        return Carbon::parse($iso8601)->utc()->format('Y-m-d H:i:s');
    }
}
