<?php

declare(strict_types=1);

namespace App\Http\Webhooks;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Переиспользуемая логика верификации входящих webhook'ов от микросервисов
 * и провайдеров (требование 10 спеки microservices-foundation).
 *
 * Подключается в {@see AbstractWebhookController} (а также в любых других
 * webhook-приёмниках), даёт три независимых проверки контракта:
 *
 *   10.2/10.3 — verifySignature(): `X-Signature: sha256=<hex>`, где
 *               `<hex>` == HMAC-SHA256(raw_body, client_secret). При
 *               отсутствии/несовпадении → 401 без побочных эффектов.
 *   10.4      — verifyTimestamp(): `X-Timestamp` (ISO-8601 UTC или unix-секунды);
 *               при `|now - ts| > maxDrift` (по умолчанию 300s) → 401.
 *   10.5      — verifyNonce(): `X-Nonce`; дедуп в Cache по ключу
 *               `webhook:nonce:{source}:{nonce}` c TTL (по умолчанию 600s).
 *               Повторный nonce → 409 Conflict.
 *
 * Все проверки «бросают» {@see HttpResponseException} с конвертом
 * `{"error":{code,message,request_id}}`, что гарантирует short-circuit ещё
 * до выполнения бизнес-логики и любых записей в БД (требование 10.3).
 *
 * Хранилище nonce — Cache-стор по умолчанию (`config('cache.default')`):
 * в проде это Redis, в тестах — array-драйвер, поэтому поведение
 * детерминировано и не требует реального Redis.
 *
 * Низкоуровневый помощник {@see hmacEquals()} переиспользуется легаси-приёмником
 * `PaymentWebhookController`, который использует собственный заголовок
 * (`X-Webhook-Signature`) и потому не вызывает verifySignature() напрямую.
 */
trait VerifiesWebhookSignature
{
    /**
     * Заголовок с HMAC-подписью нового webhook-контракта (требование 10.2).
     */
    protected string $signatureHeader = 'X-Signature';

    /**
     * Заголовок с меткой времени для replay-protection (требование 10.4).
     */
    protected string $timestampHeader = 'X-Timestamp';

    /**
     * Заголовок с одноразовым nonce для replay-protection (требование 10.5).
     */
    protected string $nonceHeader = 'X-Nonce';

    /**
     * Проверяет HMAC-SHA256 подпись запроса по заголовку `X-Signature`.
     *
     * Формат: `sha256=<hex>`, где `<hex>` == HMAC-SHA256(raw_body, $secret).
     * Сравнение — постоянного времени (hash_equals). При отсутствии заголовка
     * или несовпадении подписи бросает 401 (требования 10.2, 10.3).
     *
     * Если секрет не сконфигурирован (пустая строка) — 503: приёмник не может
     * аутентифицировать запрос, бизнес-логика не выполняется.
     */
    protected function verifySignature(Request $request, string $secret): void
    {
        if ($secret === '') {
            $this->rejectWebhook(
                Response::HTTP_SERVICE_UNAVAILABLE,
                'webhook_not_configured',
                'Webhook signing secret is not configured.',
            );
        }

        $header = (string) $request->header($this->signatureHeader, '');

        if ($header === '') {
            $this->rejectWebhook(
                Response::HTTP_UNAUTHORIZED,
                'webhook_invalid_signature',
                'Missing webhook signature.',
            );
        }

        $provided = $this->normalizeSignature($header);

        if (! $this->hmacEquals($request->getContent(), $provided, $secret)) {
            $this->rejectWebhook(
                Response::HTTP_UNAUTHORIZED,
                'webhook_invalid_signature',
                'Invalid webhook signature.',
            );
        }
    }

    /**
     * Проверяет «свежесть» запроса по заголовку `X-Timestamp` (требование 10.4).
     *
     * Принимает ISO-8601 UTC (`2024-01-01T00:00:00Z`) либо unix-секунды.
     * Отсутствие/невалидный формат, а также дрейф `|now - ts| > $maxDriftSeconds`
     * → 401. Базовый приёмник требует заголовок для защиты от replay.
     */
    protected function verifyTimestamp(Request $request, int $maxDriftSeconds = 300): void
    {
        $raw = (string) $request->header($this->timestampHeader, '');
        $timestamp = $this->parseTimestamp($raw);

        if ($timestamp === null) {
            $this->rejectWebhook(
                Response::HTTP_UNAUTHORIZED,
                'webhook_timestamp_invalid',
                'Missing or malformed X-Timestamp header.',
            );
        }

        if (abs(Carbon::now('UTC')->getTimestamp() - $timestamp) > $maxDriftSeconds) {
            $this->rejectWebhook(
                Response::HTTP_UNAUTHORIZED,
                'webhook_timestamp_invalid',
                'Webhook timestamp is outside the allowed window.',
            );
        }
    }

    /**
     * Проверяет одноразовость запроса по заголовку `X-Nonce` (требование 10.5).
     *
     * Дедуп в Cache по ключу `webhook:nonce:{source}:{nonce}` с TTL $ttlSeconds.
     * Запись производится атомарно (`add`): первый nonce сохраняется, повтор
     * в пределах TTL → 409 Conflict. Отсутствие/пустой nonce → 401.
     */
    protected function verifyNonce(Request $request, string $source, int $ttlSeconds = 600): void
    {
        $nonce = trim((string) $request->header($this->nonceHeader, ''));

        if ($nonce === '') {
            $this->rejectWebhook(
                Response::HTTP_UNAUTHORIZED,
                'webhook_nonce_invalid',
                'Missing X-Nonce header.',
            );
        }

        $key = sprintf('webhook:nonce:%s:%s', $source, $nonce);

        // `add` атомарно сохраняет значение только если ключа ещё нет.
        // false ⇒ nonce уже использован ⇒ replay ⇒ 409.
        if (! $this->nonceStore()->add($key, true, $ttlSeconds)) {
            $this->rejectWebhook(
                Response::HTTP_CONFLICT,
                'webhook_nonce_replayed',
                'Webhook nonce has already been used.',
            );
        }
    }

    /**
     * Низкоуровневое сравнение HMAC-SHA256 постоянного времени.
     *
     * Переиспользуется как новым контрактом (verifySignature), так и легаси
     * `PaymentWebhookController` (со своим заголовком `X-Webhook-Signature`).
     */
    protected function hmacEquals(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Снимает префикс `sha256=` (регистронезависимо), приводя значение к голому hex.
     */
    private function normalizeSignature(string $signature): string
    {
        $signature = trim($signature);

        if (stripos($signature, 'sha256=') === 0) {
            return substr($signature, strlen('sha256='));
        }

        return $signature;
    }

    /**
     * Парсит метку времени: unix-секунды (только цифры) либо ISO-8601.
     * Возвращает unix-секунды или null при невалидном/пустом значении.
     */
    private function parseTimestamp(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        try {
            return Carbon::parse($value)->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Хранилище nonce — Cache-стор по умолчанию (Redis в проде, array в тестах).
     */
    private function nonceStore(): CacheRepository
    {
        return Cache::store(config('cache.default'));
    }

    /**
     * Прерывает обработку webhook'а единым JSON-конвертом ошибки.
     *
     * @throws HttpResponseException
     */
    private function rejectWebhook(int $status, string $code, string $message): never
    {
        throw new HttpResponseException(
            response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'request_id' => $this->resolveRequestId(),
                ],
            ], $status)
        );
    }

    /**
     * Достаёт request_id из контейнера (проставляется AssignRequestId middleware).
     */
    private function resolveRequestId(): string
    {
        try {
            return (string) app('request_id');
        } catch (\Throwable) {
            return '';
        }
    }
}
