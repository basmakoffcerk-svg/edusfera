<?php

declare(strict_types=1);

use App\Http\Api\Internal\Controllers\InternalHealthController;
use App\Http\Api\Internal\Controllers\InternalLessonController;
use App\Http\Api\Internal\Controllers\InternalOutboxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal S2S API Routes (v1)
|--------------------------------------------------------------------------
|
| Маршруты для service-to-service (S2S) взаимодействия. Живут под
| префиксом `/api/internal/v1/*` и защищены Passport client_credentials
| (guard `api`) + middleware `scope:<needed-scope>`.
|
| Микросервисы получают access-token через `POST /oauth/token` с
| grant_type=client_credentials, затем вызывают эти маршруты с
| заголовком `Authorization: Bearer <token>`.
|
| Middleware-стек: api (AssignRequestId + StructuredLogging + RecordHttpMetrics)
| + auth:api (Passport guard) + scope:<...> + throttle:api.v1
|
| Health/liveness эндпоинт намеренно БЕЗ auth — используется для
| простой проверки доступности ядра из Docker/Kubernetes.
*/

// ─── Liveness (без auth) ─────────────────────────────────────────────
Route::get('/health', [InternalHealthController::class, 'liveness'])
    ->name('health');

// ─── Authenticated S2S routes ────────────────────────────────────────
Route::middleware('auth:api')->group(function (): void {

    // Health readiness (проверяет DB, Redis, Outbox)
    Route::get('/health/ready', [InternalHealthController::class, 'readiness'])
        ->middleware('scope:internal:metrics:read')
        ->name('health.ready');

    // Уроки — чтение
    Route::middleware('scope:lessons:read')->group(function (): void {
        Route::get('/lessons/{id}', [InternalLessonController::class, 'show'])
            ->whereNumber('id')
            ->name('lessons.show');
        Route::get('/lessons', [InternalLessonController::class, 'index'])
            ->name('lessons.index');
    });

    // Outbox — мониторинг
    Route::get('/outbox/pending', [InternalOutboxController::class, 'pending'])
        ->middleware('scope:internal:outbox:read')
        ->name('outbox.pending');
});
