<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;

/**
 * Monolog tap для лог-канала `api`: выставляет на каждом handler'е
 * канонический JSON-форматтер (`CanonicalJsonFormatter`).
 *
 * Подключается через ключ `tap` в `config/logging.php` для канала `api`:
 *   'tap' => [App\Logging\JsonFormatterTap::class].
 *
 * Обеспечивает выход каждой записи лога как одной JSON-строки с полями
 * `ts, level, request_id, route, user_id, client_id, latency_ms, message, ctx`.
 */
final class JsonFormatterTap
{
    public function __invoke(Logger $logger): void
    {
        $formatter = new CanonicalJsonFormatter;

        foreach ($logger->getLogger()->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }
    }
}
