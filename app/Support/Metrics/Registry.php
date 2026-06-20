<?php

declare(strict_types=1);

namespace App\Support\Metrics;

use Illuminate\Support\Facades\DB;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis as RedisStorage;
use Throwable;

/**
 * Обёртка над CollectorRegistry из promphp/prometheus_client_php.
 *
 * Регистрируется синглтоном в контейнере (см. {@see \App\Providers\MetricsServiceProvider}).
 * Предоставляет удобные хелперы для регистрации и обновления counters, histograms,
 * gauges, а также метод {@see render()} для экспорта в Prometheus exposition format.
 *
 * Storage adapter выбирается по `config('metrics.storage')`:
 *   in_memory — агрегация в памяти процесса (дефолт, тесты);
 *   redis     — агрегация между процессами в проде;
 *   apc       — агрегация через APCu (single-host).
 */
final class Registry
{
    private readonly CollectorRegistry $collector;

    private readonly string $namespace;

    public function __construct(?CollectorRegistry $collector = null, ?string $namespace = null)
    {
        $this->collector = $collector ?? new CollectorRegistry(self::makeStorageAdapter(), registerDefaultMetrics: false);
        $this->namespace = $namespace ?? (string) config('metrics.namespace', 'edusfera');
    }

    /**
     * Базовый CollectorRegistry (на случай прямого доступа к API библиотеки).
     */
    public function collector(): CollectorRegistry
    {
        return $this->collector;
    }

    /**
     * Зарегистрировать (или получить уже зарегистрированный) counter и увеличить его.
     *
     * @param  string  $name  имя метрики без namespace, напр. `http_requests_total`
     * @param  string  $help  человекочитаемое описание
     * @param  array<int, string>  $labelNames  имена меток, напр. ['route', 'method', 'status']
     * @param  array<int, string>  $labelValues  значения меток в том же порядке
     */
    public function incrementCounter(string $name, string $help, array $labelNames = [], array $labelValues = [], float $by = 1.0): void
    {
        $counter = $this->collector->getOrRegisterCounter($this->namespace, $name, $help, $labelNames);
        $counter->incBy($by, $labelValues);
    }

    /**
     * Зарегистрировать (или получить) histogram и наблюдать значение.
     *
     * @param  array<int, string>  $labelNames
     * @param  array<int, string>  $labelValues
     * @param  array<int, float>|null  $buckets
     */
    public function observeHistogram(string $name, string $help, float $value, array $labelNames = [], array $labelValues = [], ?array $buckets = null): void
    {
        $histogram = $this->collector->getOrRegisterHistogram($this->namespace, $name, $help, $labelNames, $buckets);
        $histogram->observe($value, $labelValues);
    }

    /**
     * Зарегистрировать (или получить) gauge и выставить его значение.
     *
     * @param  array<int, string>  $labelNames
     * @param  array<int, string>  $labelValues
     */
    public function setGauge(string $name, string $help, float $value, array $labelNames = [], array $labelValues = []): void
    {
        $gauge = $this->collector->getOrRegisterGauge($this->namespace, $name, $help, $labelNames);
        $gauge->set($value, $labelValues);
    }

    /**
     * Сэмплировать gauge `outbox_pending_total` актуальным числом неопубликованных
     * строк в таблице `integration_outbox`.
     *
     * Чтение таблицы делается best-effort: если таблицы ещё нет (миграции не
     * прогнаны) или БД недоступна — gauge просто не обновляется, эндпоинт
     * /metrics всё равно отдаёт остальные метрики.
     */
    public function sampleOutboxPending(): void
    {
        try {
            $pending = (int) DB::table('integration_outbox')
                ->whereNull('published_at')
                ->count();
        } catch (Throwable) {
            return;
        }

        $this->setGauge(
            'outbox_pending_total',
            'Number of integration_outbox rows not yet published.',
            (float) $pending,
        );
    }

    /**
     * Отрендерить все метрики в Prometheus exposition format (text/plain; version=0.0.4).
     */
    public function render(): string
    {
        $renderer = new RenderTextFormat;

        return $renderer->render($this->collector->getMetricFamilySamples(), silent: true);
    }

    /**
     * Очистить хранилище метрик (используется в тестах для изоляции).
     */
    public function wipe(): void
    {
        $this->collector->wipeStorage();
    }

    /**
     * Сконструировать storage adapter по конфигу `metrics.storage`.
     *
     * При недоступности выбранного адаптера (например, нет расширения APCu или
     * Redis) — graceful fallback на InMemory, чтобы эндпоинт /metrics не падал.
     */
    private static function makeStorageAdapter(): Adapter
    {
        $driver = (string) config('metrics.storage', 'in_memory');

        try {
            return match ($driver) {
                'redis' => new RedisStorage([
                    'host' => (string) config('metrics.redis.host', '127.0.0.1'),
                    'port' => (int) config('metrics.redis.port', 6379),
                    'password' => config('metrics.redis.password'),
                    'timeout' => (float) config('metrics.redis.timeout', 0.1),
                    'read_timeout' => (float) config('metrics.redis.read_timeout', 10),
                    'persistent_connections' => (bool) config('metrics.redis.persistent_connections', false),
                ]),
                'apc' => new APC((string) config('metrics.apc.prefix', 'prom')),
                default => new InMemory,
            };
        } catch (Throwable) {
            return new InMemory;
        }
    }
}
