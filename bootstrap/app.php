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
        // Входящие webhook'и микросервисов/провайдеров (требование 10).
        // Префикс пути — `webhooks`, middleware-группа `api` (для AssignRequestId
        // / X-Request-Id и structured logging), но БЕЗ throttle:api.v1 и без auth:
        // аутентификация делается HMAC-подписью на уровне контроллера.
        then: function (): void {
            Route::middleware('api')
                ->prefix('webhooks')
                ->name('webhooks.')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\ApplyRoleSessionLifetime::class,
        ]);

        // Группа `api` используется маршрутами `routes/api.php` (см. `withRouting()`),
        // в т.ч. подгруппой `Route::prefix('v1')` для публичного API.
        //
        // Порядок (соответствует design 3.3 microservices-foundation):
        //   1. AssignRequestId   — генерит/принимает X-Request-Id, шарит его в Log context
        //                          (требования 5.1–5.3, 5.6)
        //   2. StructuredLogging — замеряет латентность и пишет JSON-запись в канал `api`
        //                          в terminate() (требования 5.4, 5.5)
        //   …дальше идут дефолтные api-middleware (SubstituteBindings и т.д.).
        //
        // Один вызов prependToGroup сохраняет порядок переданных классов:
        // первый элемент массива станет первым в итоговом стеке.
        $middleware->prependToGroup('api', [
            \App\Http\Middleware\AssignRequestId::class,
            \App\Http\Middleware\StructuredLogging::class,
        ]);

        // Алиас `scope` для middleware EnforceServiceScope.
        // Использование: ->middleware('scope:lessons:read')
        // Покрывает требование 13.2 спеки microservices-foundation.
        //
        // Алиас `idempotency` для middleware EnforceIdempotency.
        // Вешается точечно на mutating-роуты: ->middleware('idempotency').
        // На GET/HEAD/OPTIONS и на запросы без заголовка Idempotency-Key —
        // прозрачно пропускает. Покрывает требование 4 спеки
        // microservices-foundation.
        $middleware->alias([
            'scope' => \App\Http\Middleware\EnforceServiceScope::class,
            'idempotency' => \App\Http\Middleware\EnforceIdempotency::class,
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
