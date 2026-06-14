<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
                ->get('/metrics', \App\Http\Api\V1\Controllers\MetricsController::class)
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
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\ApplyRoleSessionLifetime::class,
        ]);

        // Группа `api` используется маршрутами `routes/api.php`,
        // в т.ч. подгруппой `Route::prefix('v1')` для публичного API.
        //
        // Регистрация базовых middleware для API-запросов:
        //   1. AssignRequestId   — управление X-Request-Id для сквозного логирования.
        //   2. StructuredLogging — структурированное логирование запросов.
        $middleware->prependToGroup('api', [
            \App\Http\Middleware\AssignRequestId::class,
            \App\Http\Middleware\StructuredLogging::class,
        ]);

        // Сбор метрик HTTP-запросов. Middleware регистрируется в группе `api`
        // для замера длительности и записи метрик в Prometheus.
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\RecordHttpMetrics::class,
        ]);

        // Алиасы для middleware:
        // - `scope`: проверка прав доступа по scope.
        // - `idempotency`: обеспечение идемпотентности мутирующих запросов.
        $middleware->alias([
            'scope' => \App\Http\Middleware\EnforceServiceScope::class,
            'idempotency' => \App\Http\Middleware\EnforceIdempotency::class,
            'metrics.auth' => \App\Http\Middleware\MetricsAuth::class,
            'throttle:web.auth' => \Illuminate\Http\Middleware\ThrottleRequests::class.':web.auth',
        ]);

        $middleware->validateCsrfTokens(except: [
            '/account/*',
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
