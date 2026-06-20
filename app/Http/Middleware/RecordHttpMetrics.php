<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Metrics\Registry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Инкрементирует HTTP-метрики после обработки запроса (требования 16.4, 16.5):
 *   - counter   `http_requests_total{route, method, status}`
 *   - histogram `http_request_duration_seconds{route, method}`
 *
 * Запись метрик происходит в `terminate()` — после отправки ответа клиенту,
 * чтобы не добавлять задержку в hot path. Агрегация выполняется в storage
 * adapter (по умолчанию InMemory — память процесса), без блокирующего I/O.
 *
 * Любые ошибки записи метрик подавляются: метрики не должны ломать запрос.
 */
final class RecordHttpMetrics
{
    /**
     * Ключ в request->attributes, под которым храним точку старта запроса.
     * Атрибуты запроса безопасны при concurrent-обработке в одном воркере.
     */
    private const START_ATTR = '_record_http_metrics_started_at';

    public function __construct(private readonly Registry $registry) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::START_ATTR, microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $this->record($request, $response);
        } catch (Throwable) {
            // Метрики — best-effort. Никогда не влияем на жизненный цикл запроса.
        }
    }

    private function record(Request $request, Response $response): void
    {
        $route = $this->resolveRoute($request);
        $method = $request->getMethod();
        $status = (string) $response->getStatusCode();

        $this->registry->incrementCounter(
            'http_requests_total',
            'Total number of HTTP requests processed by the API.',
            ['route', 'method', 'status'],
            [$route, $method, $status],
        );

        $startedAt = $request->attributes->get(self::START_ATTR);
        if (is_float($startedAt)) {
            $durationSeconds = microtime(true) - $startedAt;

            $this->registry->observeHistogram(
                'http_request_duration_seconds',
                'HTTP request duration in seconds.',
                $durationSeconds,
                ['route', 'method'],
                [$route, $method],
                $this->durationBuckets(),
            );
        }
    }

    /**
     * Имя метрики route — это имя зарегистрированного роута, иначе нормализованный
     * путь. Использование route name (а не raw path) предотвращает «взрыв
     * кардинальности» меток на путях с параметрами (напр. /lessons/{id}).
     */
    private function resolveRoute(Request $request): string
    {
        $route = $request->route();

        if (is_object($route) && method_exists($route, 'getName')) {
            $name = $route->getName();
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        if (is_object($route) && method_exists($route, 'uri')) {
            $uri = $route->uri();
            if (is_string($uri) && $uri !== '') {
                return '/'.ltrim($uri, '/');
            }
        }

        return '/'.ltrim($request->path(), '/');
    }

    /**
     * @return array<int, float>
     */
    private function durationBuckets(): array
    {
        $buckets = config('metrics.duration_buckets');

        if (is_array($buckets) && $buckets !== []) {
            return array_map(static fn ($b): float => (float) $b, array_values($buckets));
        }

        return [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0];
    }
}
