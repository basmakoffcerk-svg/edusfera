<?php

declare(strict_types=1);

use App\Http\Api\V1\Controllers\ClassroomTokenController;
use App\Http\Api\V1\Controllers\JwksController;
use App\Http\Api\V1\Controllers\MeController;
use App\Http\Controllers\PromoLeadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| Публичный REST API ядра. Все маршруты живут под префиксом `/api/v1/*`
| (внешний префикс `api` задан в bootstrap/app.php->withRouting()).
|
| Любые роуты, добавляемые в эту группу, должны валидироваться через
| FormRequest и возвращать ответы через DTO в `app/Http/Api/V1/Resources/`,
| а не сериализовать Eloquent-модели напрямую (см. требования 1.1, 1.2, 6.6).
|
| Middleware-стек (AssignRequestId → StructuredLogging → ApiAuthenticate →
| EnforceServiceScope → EnforceIdempotency → ThrottleApi) подключается
| в последующих задачах фазы 1 (см. tasks.md, задачи 2–4).
*/

// Публичный роут для приема заявок с промо-лендинга
Route::post('/promo/leads', [PromoLeadController::class, 'store'])->name('promo.leads.store');

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('throttle:api.v1') // rate-limit api.v1 (требование 17, задача 4)
    ->group(function (): void {
        // Маршруты v1 будут зарегистрированы в последующих задачах:
        //  - GET  /api/v1/me                              (задача 5)
        //  - GET  /api/v1/.well-known/jwks.json           (задача 16)
        //  - POST /api/v1/auth/tokens                     (Sanctum personal access tokens)
        //  - GET  /api/v1/lessons/{id}/classroom-token    (задача 22)

        // Smoke-эндпоинт под Sanctum-аутентификацией (требования 1.5, 2.3, 2.4).
        // Без валидного токена middleware `auth:sanctum` возвращает 401.
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', MeController::class)->name('me');

            // Classroom-токен для подключения пользователя к виртуальному классу
            // (требования 11.5, 11.6). Доступ к уроку проверяется внутри
            // ClassroomTokenService через LessonPolicy::view; 403 — без прав,
            // 404 — если урок не найден.
            //
            // NB: scope `classroom:token:issue` намеренно НЕ навешан на этот
            // пользовательский путь — Sanctum-токены пользователей такого scope
            // не несут, и его проверка сломала бы сценарий 11.5. Для S2S-доступа
            // микросервисов предполагается отдельный роут под Passport
            // client_credentials с `->middleware('scope:classroom:token:issue')`
            // (вводится вместе с internal-маршрутами, см. требование 13.6).
            Route::get('/lessons/{id}/classroom-token', ClassroomTokenController::class)
                ->name('lessons.classroom-token');
        });

        // Публичный JWKS endpoint — без auth middleware (требования 3.4, 3.7, 11.7).
        // Микросервисы используют его для самостоятельной валидации RS256-токенов.
        Route::get('/.well-known/jwks.json', JwksController::class)->name('jwks');
    });
