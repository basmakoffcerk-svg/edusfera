<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Заголовок `X-Request-Id` пробрасывается через всю цепочку обработки запроса:
 * принимается из входящего запроса (если соответствует UUID v4 или v7),
 * либо генерируется как UUID v7. Значение помещается в контейнер под ключом
 * `request_id`, шарится в контекст логов через `Log::shareContext` и
 * выставляется на исходящий ответ.
 *
 * Покрывает требования 5.1, 5.2, 5.3, 5.6 спеки microservices-foundation.
 */
final class AssignRequestId
{
    /**
     * Имя HTTP-заголовка, в котором передаётся идентификатор запроса.
     */
    public const HEADER = 'X-Request-Id';

    /**
     * Ключ в сервис-контейнере, под которым публикуется значение `request_id`,
     * чтобы любой код мог достать его через `app('request_id')`.
     */
    public const CONTAINER_KEY = 'request_id';

    /**
     * Регулярное выражение для UUID v4 / v7 (RFC 4122).
     * Допускаются hex-символы в любом регистре; вариантный бит в [89ab].
     */
    private const UUID_V4_OR_V7_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-[47][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

    public function __construct(
        private readonly Application $app,
        private readonly LogManager $log,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolveRequestId($request);

        // 1. Положить в контейнер: app('request_id') === $requestId.
        $this->app->instance(self::CONTAINER_KEY, $requestId);

        // 2. Положить в общий контекст логов, чтобы любая запись содержала request_id.
        $this->log->shareContext([self::CONTAINER_KEY => $requestId]);

        // 3. Положить в сам Request, чтобы downstream-middleware видели заголовок,
        //    даже если клиент его не прислал.
        $request->headers->set(self::HEADER, $requestId);

        $response = $next($request);

        // 4. Выставить X-Request-Id на исходящем ответе.
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }

    /**
     * Если клиент прислал валидный UUID v4/v7, используем его; иначе
     * генерируем новый UUID v7.
     */
    private function resolveRequestId(Request $request): string
    {
        $incoming = $request->headers->get(self::HEADER);

        if (is_string($incoming) && $this->isValidUuidV4OrV7($incoming)) {
            return strtolower($incoming);
        }

        return (string) Str::uuid7();
    }

    private function isValidUuidV4OrV7(string $value): bool
    {
        return preg_match(self::UUID_V4_OR_V7_REGEX, $value) === 1;
    }
}
