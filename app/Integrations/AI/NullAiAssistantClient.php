<?php

declare(strict_types=1);

namespace App\Integrations\AI;

use App\Contracts\Integrations\AiAssistantClient;

/**
 * Заглушка клиента AI-сервиса для фундамента (требование 15.1).
 *
 * AI-сервиса ещё не существует, поэтому реализация возвращает пустой набор
 * обработанных `lesson_id`. С точки зрения reconciliation это означает, что
 * AI «ничего не обработал» — и команда `reconcile:lessons-with-ai` повторно
 * опубликует `lesson.completed.v1` для всех completed-уроков ядра за период
 * (с защитой idempotency-окном 24 часа).
 *
 * Реальный HTTP-клиент придёт в feature-spec'е AI-ассистента и заменит этот
 * биндинг в `AppServiceProvider` без изменения команды.
 */
final class NullAiAssistantClient implements AiAssistantClient
{
    /**
     * {@inheritDoc}
     *
     * @return list<int>
     */
    public function processedLessonIds(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return [];
    }
}
