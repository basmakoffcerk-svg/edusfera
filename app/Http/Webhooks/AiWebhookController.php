<?php

declare(strict_types=1);

namespace App\Http\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Приёмник webhook'ов от AI-сервиса (`POST /webhooks/ai/recommendations`).
 *
 * Пока это заглушка-пример переиспользования {@see AbstractWebhookController}:
 * выполняет полный контракт проверок (HMAC `X-Signature` секретом
 * `services.ai.webhook_secret`, `X-Timestamp` ±5 мин, `X-Nonce` с дедупом) и при
 * успехе возвращает 200 `{"status":"accepted"}`. Реальная доменная обработка
 * (создание `HomeworkAssignment` с `source='ai'`) выносится в отдельную
 * feature-спеку — поэтому здесь намеренно нет обращений к Eloquent/DB
 * (требование 14.5).
 *
 * Источник nonce — `ai`, ключ дедупа: `webhook:nonce:ai:{nonce}`.
 */
final class AiWebhookController extends AbstractWebhookController
{
    protected function source(): string
    {
        return 'ai';
    }

    protected function signingSecret(): string
    {
        return (string) config('services.ai.webhook_secret', '');
    }

    protected function handle(Request $request): JsonResponse
    {
        // Заглушка: проверки уже пройдены в template-method __invoke().
        // Реальная обработка рекомендаций — отдельная feature-спека.
        return response()->json(['status' => 'accepted']);
    }
}
