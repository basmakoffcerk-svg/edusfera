<?php

declare(strict_types=1);

namespace App\Http\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Базовый приёмник webhook'ов от внешних сервисов и провайдеров
 * (требования 10.1–10.7 спеки microservices-foundation).
 *
 * Реализует template-method {@see __invoke()}: выполняет три проверки контракта
 * строго по порядку (подпись → timestamp → nonce) и только затем делегирует
 * управление доменному обработчику {@see handle()}. Любая неуспешная проверка
 * бросает HttpResponseException и прерывает pipeline ДО выполнения бизнес-логики
 * и любой записи в БД (требование 10.3, 10.6).
 *
 * Наследники задают источник ({@see source()}) и секрет ({@see signingSecret()}),
 * а также реализуют доменную обработку в {@see handle()}.
 *
 * Контракт проверок намеренно вынесен в трейт {@see VerifiesWebhookSignature},
 * чтобы его можно было переиспользовать и в контроллерах вне этой иерархии
 * (требование 10.7).
 */
abstract class AbstractWebhookController extends Controller
{
    use VerifiesWebhookSignature;

    /**
     * Максимально допустимый дрейф `X-Timestamp` в секундах (требование 10.4).
     */
    protected int $maxTimestampDriftSeconds = 300;

    /**
     * TTL дедупа nonce в секундах (требование 10.5).
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
     * Per-client секрет для проверки HMAC-подписи (требование 10.2).
     */
    abstract protected function signingSecret(): string;

    /**
     * Доменная обработка валидного webhook'а (требование 10.6).
     * Вызывается только после успешных проверок подписи, timestamp и nonce.
     */
    abstract protected function handle(Request $request): JsonResponse;
}
