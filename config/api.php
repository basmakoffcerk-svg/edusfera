<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Throttle policy для публичного API (/api/v1/*)
    |--------------------------------------------------------------------------
    |
    | Лимиты частоты запросов для именованного rate-limiter'а `api.v1`
    | (см. App\Providers\RouteServiceProvider и требование 17 спеки
    | microservices-foundation).
    |
    |  - user   — лимит на аутентифицированного Sanctum-пользователя (per user_id)
    |  - client — лимит на service-токен Passport client_credentials (per client_id)
    |  - ip     — fallback-лимит для неаутентифицированных запросов (per IP)
    |
    | Значения вынесены в конфиг, чтобы их можно было переопределять через ENV
    | и детерминированно понижать в тестах.
    */

    'throttle' => [
        'user' => (int) env('API_THROTTLE_USER_PER_MINUTE', 60),
        'client' => (int) env('API_THROTTLE_CLIENT_PER_MINUTE', 600),
        'ip' => (int) env('API_THROTTLE_IP_PER_MINUTE', 60),
    ],

];
