<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Metrics\Registry;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует {@see Registry} как синглтон в контейнере.
 *
 * Единый экземпляр Registry (и обёрнутый им CollectorRegistry) живёт всё время
 * жизни процесса, что обеспечивает агрегацию метрик в памяти процесса между
 * запросами в рамках одного воркера (требование 16.5).
 *
 * Покрывает требование 16 спеки microservices-foundation.
 */
final class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Registry::class, fn (): Registry => new Registry);
    }
}
