<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

/**
 * JSON-форматтер канала `api`, выдающий ровно канонический набор полей:
 * `ts, level, request_id, route, user_id, client_id, latency_ms, message, ctx`.
 *
 * Зарезервированные ключи (`request_id, route, user_id, client_id, latency_ms`)
 * извлекаются из `context`/`extra` LogRecord (их кладут туда `Log::shareContext`
 * в `AssignRequestId` и middleware `StructuredLogging`). Всё остальное содержимое
 * `context`/`extra` сохраняется в поле `ctx`.
 */
final class CanonicalJsonFormatter extends JsonFormatter
{
    /**
     * Поля, которые поднимаются на верхний уровень JSON-записи и
     * не дублируются внутрь `ctx`.
     *
     * @var list<string>
     */
    private const RESERVED_KEYS = [
        'request_id',
        'route',
        'user_id',
        'client_id',
        'latency_ms',
    ];

    public function __construct()
    {
        // BATCH_MODE_NEWLINES + appendNewline=true: одна запись = одна JSON-строка.
        parent::__construct(
            batchMode: self::BATCH_MODE_NEWLINES,
            appendNewline: true,
            ignoreEmptyContextAndExtra: false,
            includeStacktraces: false,
        );
    }

    public function format(LogRecord $record): string
    {
        $context = $record->context;
        $extra = $record->extra;

        $reserved = [];
        foreach (self::RESERVED_KEYS as $key) {
            if (array_key_exists($key, $context)) {
                $reserved[$key] = $context[$key];
                unset($context[$key]);

                continue;
            }

            if (array_key_exists($key, $extra)) {
                $reserved[$key] = $extra[$key];
                unset($extra[$key]);
            }
        }

        // Остаток контекста + extra едет в `ctx`. `extra` сохраняем как
        // вложенный объект, чтобы не путать с пользовательским контекстом.
        $ctx = $context;
        if ($extra !== []) {
            $ctx['extra'] = $extra;
        }

        $payload = [
            'ts' => $record->datetime->format('Y-m-d\TH:i:s.uP'),
            'level' => strtolower($record->level->getName()),
            'request_id' => $reserved['request_id'] ?? null,
            'route' => $reserved['route'] ?? null,
            'user_id' => $reserved['user_id'] ?? null,
            'client_id' => $reserved['client_id'] ?? null,
            'latency_ms' => $reserved['latency_ms'] ?? null,
            'message' => $record->message,
            'ctx' => (object) $ctx,
        ];

        $normalized = $this->normalize($payload);

        return $this->toJson($normalized, true).($this->appendNewline ? "\n" : '');
    }
}
