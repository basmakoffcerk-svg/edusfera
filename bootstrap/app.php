<?php

require_once __DIR__.'/../app/Support/bcmath_polyfill.php';

use App\Http\Api\V1\Controllers\MetricsController;
use App\Http\Middleware\ApplyRoleSessionLifetime;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnforceIdempotency;
use App\Http\Middleware\EnforceServiceScope;
use App\Http\Middleware\MetricsAuth;
use App\Http\Middleware\RecordHttpMetrics;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StructuredLogging;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Входящие webhook'и микросервисов/провайдеров.
        // Префикс пути — `webhooks`, middleware-группа `api` (для AssignRequestId
        // и structured logging), но без throttle и auth.
        // Аутентификация выполняется HMAC-подписью на уровне контроллера.
        then: function (): void {
            Route::middleware('api')
                ->prefix('webhooks')
                ->name('webhooks.')
                ->group(base_path('routes/webhooks.php'));

            // Эндпоинт метрик Prometheus.
            // Регистрируется на корневом уровне как `GET /metrics`,
            // чтобы быть доступным для стандартного сбора (scrape).
            // Защищен с помощью middleware `metrics.auth`.
            Route::middleware(['api', 'metrics.auth'])
                ->get('/metrics', MetricsController::class)
                ->name('metrics');

            // Internal S2S API для микросервисов.
            // Префикс `/api/internal/v1`, middleware-группа `api` (AssignRequestId,
            // StructuredLogging, RecordHttpMetrics). Аутентификация и scope-проверка
            // определяются внутри routes/internal.php per-route.
            Route::middleware('api')
                ->prefix('api/internal/v1')
                ->name('internal.v1.')
                ->group(base_path('routes/internal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // M7: Global throttle on web routes — prevents brute-force and DDoS.
        // 120 req/min per IP is generous for a tutor marketplace.
        $middleware->appendToGroup('web', [
            ApplyRoleSessionLifetime::class,
            SecurityHeaders::class,
            'throttle:120,1',
        ]);

        $middleware->appendToGroup('api', [
            SecurityHeaders::class,
        ]);

        // Группа `api` используется маршрутами `routes/api.php`,
        // в т.ч. подгруппой `Route::prefix('v1')` для публичного API.
        //
        // Регистрация базовых middleware для API-запросов:
        //   1. AssignRequestId   — управление X-Request-Id для сквозного логирования.
        //   2. StructuredLogging — структурированное логирование запросов.
        $middleware->prependToGroup('api', [
            AssignRequestId::class,
            StructuredLogging::class,
        ]);

        // Сбор метрик HTTP-запросов. Middleware регистрируется в группе `api`
        // для замера длительности и записи метрик в Prometheus.
        $middleware->appendToGroup('api', [
            RecordHttpMetrics::class,
        ]);

        // Алиасы для middleware:
        // - `scope`: проверка прав доступа по scope.
        // - `idempotency`: обеспечение идемпотентности мутирующих запросов.
        $middleware->alias([
            'scope' => EnforceServiceScope::class,
            'idempotency' => EnforceIdempotency::class,
            'metrics.auth' => MetricsAuth::class,
            'throttle:web.auth' => ThrottleRequests::class.':web.auth',
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/internal/classroom/*/whiteboard',
            'payments/webhook',
            'payments/alfabank/webhook',
            'webhooks/alfabank',
            'api/v1/payments/alfabank/webhook',
            'payments/webpay/webhook',
            'webhooks/webpay',
        ]);
    })
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('lessons:complete')->everyTenMinutes();
        $schedule->command('integration:publish-outbox')->everyMinute();
        $schedule->command('integration:cleanup-outbox')->dailyAt('03:30');
        $schedule->command('reconcile:lessons-with-ai')->dailyAt('03:00');
        $schedule->command('health:check')->daily()->at('06:00');
        $schedule->command('queue:prune-batches --hours=48')->daily();
        $schedule->command('queue:prune-failed --hours=168')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
