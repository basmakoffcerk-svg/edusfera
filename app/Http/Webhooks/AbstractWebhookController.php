<?php

declare(strict_types=1);

namespace App\Http\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Базовый приёмник webhook'ов от внешних сервисов и провайдеров.
 *
 * Выполняет три проверки контракта строго по порядку (подпись → timestamp → nonce)
 * и только затем делегирует управление доменному обработчику {@see handle()}.
 * Любая неуспешная проверка прерывает pipeline ДО выполнения бизнес-логики
 * и любых записей в БД.
 *
 * Наследники задают источник ({@see source()}) и секрет ({@see signingSecret()}),
 * а также реализуют доменную обработку в {@see handle()}.
 */
abstract class AbstractWebhookController extends Controller
{
    use VerifiesWebhookSignature;

    /**
     * Максимально допустимый дрейф `X-Timestamp` в секундах.
     */
    protected int $maxTimestampDriftSeconds = 300;

    /**
     * TTL дедупликации nonce в секундах.
     */
    protected int $nonceTtlSeconds = 600;

    /**
     * Template-method: верифицирует запрос и делегирует обработку наследнику.
     */
    public function __invoke(Request $request): Response
    {
        $this->verifySignature($request, $this->signingSecret());
        $this->verifyTimestamp($request, $this->maxTimestampDriftSeconds);
        $this->verifyNonce($request, $this->source(), $this->nonceTtlSeconds);

        return $this->handle($request);
    }

    /**
     * Логический идентификатор источника webhook'а (например, `ai`, `video`).
     * Используется как пространство имён в ключе nonce.
     */
    abstract protected function source(): string;

    /**
     * Per-client секрет для проверки HMAC-подписи.
     */
    abstract protected function signingSecret(): string;

    /**
     * Доменная обработка валидного webhook'а.
     */
    abstract protected function handle(Request $request): JsonResponse;
}
