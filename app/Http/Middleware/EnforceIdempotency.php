<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware идемпотентности mutating-эндпоинтов через заголовок `Idempotency-Key`.
 *
 * Применяется точечно как route-алиас `idempotency` к mutating-роутам
 * (POST/PUT/PATCH/DELETE). Для безопасных методов (GET/HEAD/OPTIONS) и для
 * запросов без заголовка `Idempotency-Key` — прозрачно пропускает запрос.
 *
 * Хранилище — Laravel Cache (поверх Redis в проде, array в тестах). Ключ имеет
 * вид `idem:v1:{client_id}:{key}` с per-client пространством имён. Под ключом
 * лежит один hash-объект, описывающий состояние запроса:
 *
 *   - in-flight: {state: "in-flight", body_hash}                       TTL 60s
 *   - cached:    {state: "cached", body_hash, status, headers, body}   TTL 86400s
 *
 * Состояния (требование 4 спеки microservices-foundation):
 *   4.1 — mutating-метод без `Idempotency-Key`            → pass-through без кеширования.
 *   4.2 — ключ кеша `idem:v1:{client_id}:{key}`.
 *   4.3 — ключа нет                                       → in-flight, выполняем роут,
 *                                                           затем кэшируем ответ (24 ч).
 *   4.4 — есть закэшированный ответ                       → реплей того же ответа без роута.
 *   4.5 — ключ помечен in-flight                          → 409 idempotency_in_flight.
 *   4.6 — тот же ключ, но другой sha256(raw_body)         → 422 idempotency_body_mismatch.
 *   4.7 — пустой ключ или длиной > 255 символов           → 400 idempotency_key_invalid.
 *
 * Приоритет проверок при существующей записи: сначала сверяется hash тела
 * (4.6 важнее), затем состояние (in-flight → 409, cached → реплей). Это защищает
 * от misuse, когда один и тот же ключ переиспользуется с другим телом.
 */
final class EnforceIdempotency
{
    /**
     * Имя HTTP-заголовка с ключом идемпотентности.
     */
    public const HEADER = 'Idempotency-Key';

    /**
     * Заголовок, добавляемый к воспроизведённому (cached) ответу, чтобы клиент
     * мог отличить реплей от свежего выполнения роута.
     */
    public const REPLAYED_HEADER = 'Idempotency-Replayed';

    /**
     * Методы, для которых идемпотентность имеет смысл (mutating).
     */
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Максимально допустимая длина значения `Idempotency-Key`.
     */
    private const KEY_MAX_LENGTH = 255;

    /**
     * TTL закэшированного ответа — 24 часа (требование 4.3).
     */
    private const RESPONSE_TTL_SECONDS = 86400;

    /**
     * TTL маркера in-flight — окно на выполнение исходного запроса (требование 4.5).
     */
    private const IN_FLIGHT_TTL_SECONDS = 60;

    private const STATE_IN_FLIGHT = 'in-flight';

    private const STATE_CACHED = 'cached';

    public function handle(Request $request, Closure $next): Response
    {
        // 4.1: идемпотентность только для mutating-методов. Безопасные методы
        // (GET/HEAD/OPTIONS) пропускаем без какой-либо обработки.
        if (! in_array($request->getMethod(), self::MUTATING_METHODS, true)) {
            return $next($request);
        }

        // 4.1: mutating-запрос без заголовка `Idempotency-Key` пропускаем без кеширования.
        if (! $request->headers->has(self::HEADER)) {
            return $next($request);
        }

        $key = (string) $request->headers->get(self::HEADER);

        // 4.7: пустой ключ или длиннее 255 символов → 400 idempotency_key_invalid.
        if ($key === '' || mb_strlen($key) > self::KEY_MAX_LENGTH) {
            return $this->errorResponse(
                'idempotency_key_invalid',
                'The Idempotency-Key header must be a non-empty string of at most 255 characters.',
                Response::HTTP_BAD_REQUEST,
            );
        }

        $cache = $this->cache();
        $cacheKey = $this->cacheKey($request, $key);
        $bodyHash = hash('sha256', $request->getContent());

        $record = $cache->get($cacheKey);

        if (is_array($record)) {
            // 4.6: тот же ключ, но другое тело → 422 (имеет приоритет над состоянием).
            if (($record['body_hash'] ?? null) !== $bodyHash) {
                return $this->errorResponse(
                    'idempotency_body_mismatch',
                    'The Idempotency-Key has already been used with a different request body.',
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            // 4.5: запрос с тем же ключом ещё выполняется → 409 Conflict.
            if (($record['state'] ?? null) === self::STATE_IN_FLIGHT) {
                return $this->errorResponse(
                    'idempotency_in_flight',
                    'A request with the same Idempotency-Key is already being processed.',
                    Response::HTTP_CONFLICT,
                );
            }

            // 4.4: есть закэшированный ответ → возвращаем его, не вызывая роут.
            if (($record['state'] ?? null) === self::STATE_CACHED) {
                return $this->replay($record);
            }
        }

        // 4.3: ключа нет → ставим маркер in-flight (TTL 60s) и пропускаем запрос дальше.
        $cache->put($cacheKey, [
            'state' => self::STATE_IN_FLIGHT,
            'body_hash' => $bodyHash,
        ], self::IN_FLIGHT_TTL_SECONDS);

        $response = $next($request);

        // 4.3: после ответа сохраняем {status, headers, body, body_hash} на 24 часа.
        $cache->put($cacheKey, [
            'state' => self::STATE_CACHED,
            'body_hash' => $bodyHash,
            'status' => $response->getStatusCode(),
            'headers' => $this->capturableHeaders($response),
            'body' => $response->getContent(),
        ], self::RESPONSE_TTL_SECONDS);

        return $response;
    }

    /**
     * Формирует cache-ключ `idem:v1:{client_id}:{key}` (требование 4.2).
     */
    private function cacheKey(Request $request, string $key): string
    {
        return sprintf('idem:v1:%s:%s', $this->resolveClientId($request), $key);
    }

    /**
     * Определяет пространство имён клиента для ключа:
     *  1. Passport client_credentials — request-атрибут `oauth_client_id`
     *     (проставляется ApiAuthenticate) или одноимённый биндинг в контейнере.
     *  2. Sanctum-пользователь — `user:{id}`.
     *  3. Иначе — `anonymous`.
     */
    private function resolveClientId(Request $request): string
    {
        $fromRequest = $request->attributes->get('oauth_client_id');
        if (is_string($fromRequest) && $fromRequest !== '') {
            return $fromRequest;
        }

        try {
            $container = app();
            if ($container->bound('oauth_client_id')) {
                $fromContainer = $container->make('oauth_client_id');
                if (is_string($fromContainer) && $fromContainer !== '') {
                    return $fromContainer;
                }
            }
        } catch (\Throwable) {
            // graceful fallback — продолжаем к Sanctum/anonymous.
        }

        $user = $request->user();
        if ($user !== null) {
            return 'user:'.$user->getAuthIdentifier();
        }

        return 'anonymous';
    }

    /**
     * Воспроизводит закэшированный ответ (требование 4.4): тот же статус, тело и
     * сохранённые заголовки + служебный `Idempotency-Replayed: true`.
     *
     * @param  array<string, mixed>  $record
     */
    private function replay(array $record): Response
    {
        $response = response(
            $record['body'] ?? '',
            (int) ($record['status'] ?? Response::HTTP_OK),
        );

        $headers = $record['headers'] ?? [];
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                $response->headers->set((string) $name, $value);
            }
        }

        $response->headers->set(self::REPLAYED_HEADER, 'true');

        return $response;
    }

    /**
     * Выбирает заголовки ответа, которые имеет смысл сохранять и воспроизводить.
     * Сейчас это `Content-Type`, чтобы клиент корректно интерпретировал тело.
     *
     * @return array<string, string|null>
     */
    private function capturableHeaders(Response $response): array
    {
        $captured = [];

        if ($response->headers->has('Content-Type')) {
            $captured['Content-Type'] = $response->headers->get('Content-Type');
        }

        return $captured;
    }

    /**
     * Хранилище идемпотентности — Cache-стор по умолчанию (`config('cache.default')`).
     * В проде это Redis, в тестах — array-драйвер, что делает поведение
     * детерминированным и не требует реального Redis.
     */
    private function cache(): CacheRepository
    {
        return Cache::store(config('cache.default'));
    }

    /**
     * Формирует ответ-ошибку в едином конверте `{"error":{code,message,request_id}}`.
     */
    private function errorResponse(string $code, string $message, int $status): Response
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'request_id' => $this->resolveRequestId(),
            ],
        ], $status);
    }

    /**
     * Достаёт request_id из контейнера (проставляется AssignRequestId middleware).
     * Graceful fallback — пустая строка, если middleware ещё не запускался.
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
