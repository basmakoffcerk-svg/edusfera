<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Замеряет длительность обработки запроса и пишет структурированную запись
 * в лог-канал `api` после отправки ответа клиенту (`terminate()`).
 *
 * Запись содержит поля: `route`, `method`, `status`, `latency_ms`, `user_id`,
 * `client_id`. Поле `request_id` подмешивается автоматически из общего
 * контекста, который выставляет `AssignRequestId` через `Log::shareContext`.
 */
final class StructuredLogging
{
    /**
     * Ключ в request->attributes, под которым храним точку старта запроса.
     * Использование `attributes` (а не свойств класса) гарантирует корректность
     * при concurrent-запросах в одном воркере (Octane / FrankenPHP / тесты).
     */
    private const START_ATTR = '_structured_logging_started_at';

    public function __construct(
        private readonly Application $app,
        private readonly LogManager $log,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::START_ATTR, microtime(true));

        return $next($request);
    }

    /**
     * Вызывается фреймворком после отправки ответа клиенту, что позволяет
     * писать в лог-канал без задержки ответа.
     */
    public function terminate(Request $request, Response $response): void
    {
        $startedAt = $request->attributes->get(self::START_ATTR);
        $latencyMs = is_float($startedAt)
            ? (int) round((microtime(true) - $startedAt) * 1000)
            : 0;

        $route = $request->route();
        $routeName = is_object($route) && method_exists($route, 'getName')
            ? $route->getName()
            : null;
        $routeIdentifier = is_string($routeName) && $routeName !== ''
            ? $routeName
            : '/'.ltrim($request->path(), '/');

        $userId = null;
        try {
            $userId = $request->user()?->getAuthIdentifier();
        } catch (\Throwable) {
            // Если auth-резолвер не сконфигурирован — это нормально для
            // публичных роутов и тестов; просто не пишем user_id.
        }

        $clientId = $this->resolveClientId($request);

        $context = [
            'route' => $routeIdentifier,
            'method' => $request->getMethod(),
            'status' => $response->getStatusCode(),
            'latency_ms' => $latencyMs,
            'user_id' => $userId,
            'client_id' => $clientId,
        ];

        $this->log->channel('api')->info('http_request_completed', $context);
    }

    /**
     * Чтение client_id из запроса или контейнера.
     */
    private function resolveClientId(Request $request): ?string
    {
        $fromRequest = $request->attributes->get('oauth_client_id');
        if (is_string($fromRequest) && $fromRequest !== '') {
            return $fromRequest;
        }

        if ($this->app->bound('oauth_client_id')) {
            $fromContainer = $this->app->make('oauth_client_id');
            if (is_string($fromContainer) && $fromContainer !== '') {
                return $fromContainer;
            }
        }

        return null;
    }
}
