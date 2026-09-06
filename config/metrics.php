<?php

declare(strict_types=1);

/**
 * Конфигурация метрик Prometheus и эндпоинта /metrics.
 *
 * Покрывает требование 16 спеки microservices-foundation:
 *  - 16.1 пакет promphp/prometheus_client_php как реестр метрик
 *  - 16.2 эндпоинт /metrics в формате Prometheus exposition
 *  - 16.3 защита эндпоинта (scope internal:metrics:read ИЛИ basic-auth)
 *  - 16.4 каталог экспортируемых метрик
 *  - 16.5 агрегация в памяти процесса (storage adapter)
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Namespace метрик
    |--------------------------------------------------------------------------
    |
    | Prefix, добавляемый ко всем именам метрик (например, `edusfera_`).
    | Так все метрики приложения группируются в Prometheus по namespace.
    |
    */
    'namespace' => env('METRICS_NAMESPACE', 'edusfera'),

    /*
    |--------------------------------------------------------------------------
    | Способ защиты /metrics (требование 16.3)
    |--------------------------------------------------------------------------
    |
    | scope      — требуется service-токен со scope `internal:metrics:read`
    |              (проверяется так же, как EnforceServiceScope).
    | basic_auth — требуется HTTP Basic auth с парой user/password ниже.
    |
    | В обоих случаях при отсутствии валидных кредов эндпоинт отдаёт 401.
    |
    */
    'protection' => env('METRICS_PROTECTION', 'scope'),

    'scope' => env('METRICS_SCOPE', 'internal:metrics:read'),

    'basic_auth' => [
        'user' => env('METRICS_BASIC_USER'),
        'password' => env('METRICS_BASIC_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage adapter (требование 16.5)
    |--------------------------------------------------------------------------
    |
    | in_memory — Prometheus\Storage\InMemory. Агрегация в памяти процесса,
    |             без внешних зависимостей. Дефолт для надёжности и тестов.
    | redis     — Prometheus\Storage\Redis. Агрегация между процессами/воркерами
    |             в проде (требует доступного Redis).
    | apc       — Prometheus\Storage\APC. Агрегация через APCu (single-host).
    |
    */
    'storage' => env('METRICS_STORAGE', 'in_memory'),

    'redis' => [
        'host' => env('METRICS_REDIS_HOST', env('REDIS_HOST', '127.0.0.1')),
        'port' => (int) env('METRICS_REDIS_PORT', env('REDIS_PORT', 6379)),
        'password' => env('METRICS_REDIS_PASSWORD', env('REDIS_PASSWORD')),
        'timeout' => (float) env('METRICS_REDIS_TIMEOUT', 0.1),
        'read_timeout' => (float) env('METRICS_REDIS_READ_TIMEOUT', 10),
        'persistent_connections' => (bool) env('METRICS_REDIS_PERSISTENT', false),
    ],

    'apc' => [
        'prefix' => env('METRICS_APC_PREFIX', 'prom'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Histogram buckets для http_request_duration_seconds
    |--------------------------------------------------------------------------
    |
    | Границы корзин (в секундах) для гистограммы длительности запросов.
    |
    */
    'duration_buckets' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0],

];
