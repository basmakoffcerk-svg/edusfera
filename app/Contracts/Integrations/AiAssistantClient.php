<?php

declare(strict_types=1);

namespace App\Contracts\Integrations;

/**
 * Контракт клиента AI-сервиса (репетиторский ассистент).
 *
 * Используется reconciliation-командой `reconcile:lessons-with-ai` (требование 15.1)
 * для сверки состояния ядра с состоянием микросервиса: команда спрашивает у
 * AI-сервиса, какие `lesson_id` тот уже обработал за период, и публикует
 * компенсирующие события `lesson.completed.v1` для расхождений.
 *
 * В фундаменте (этот спек) самого AI-сервиса ещё нет, поэтому контракт
 * реализуется заглушкой `App\Integrations\AI\NullAiAssistantClient`,
 * возвращающей пустой набор. Реальная реализация (HTTP-клиент с S2S-токеном
 * и X-Request-Id) появится в отдельном feature-spec'е AI-ассистента.
 */
interface AiAssistantClient
{
    /**
     * Список `lesson_id`, обработанных AI-сервисом за период `[$from, $to]`.
     *
     * @param  \DateTimeInterface  $from  начало периода (включительно)
     * @param  \DateTimeInterface  $to  конец периода (включительно)
     * @return list<int> набор обработанных lesson_id (может быть пустым)
     */
    public function processedLessonIds(\DateTimeInterface $from, \DateTimeInterface $to): array;
}
