<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\Middleware\EnforceIdempotency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Покрывает требование 4 спеки microservices-foundation (EnforceIdempotency):
 *  - 4.1: mutating-запрос без `Idempotency-Key` → роут выполняется, ответ не кешируется.
 *  - 4.2: ключ кеша формируется как `idem:v1:{client_id}:{key}`.
 *  - 4.3: первый запрос с ключом → роут выполняется, ответ кэшируется (24 ч).
 *  - 4.4: повторный запрос с тем же ключом/телом → закэшированный ответ без роута.
 *  - 4.5: ключ помечен in-flight → 409 idempotency_in_flight.
 *  - 4.6: тот же ключ, другое тело → 422 idempotency_body_mismatch.
 *  - 4.7: пустой ключ или > 255 символов → 400 idempotency_key_invalid.
 *
 * Хранилище идемпотентности — Cache::store(config('cache.default')); в тестах
 * это array-драйвер (CACHE_STORE=array из phpunit.xml), что делает поведение
 * детерминированным без реального Redis. Мутирующий тестовый роут вешается под
 * middleware-алиас `idempotency` и считает вызовы через статический счётчик.
 */
class IdempotencyMiddlewareTest extends TestCase
{
    /**
     * Счётчик фактических выполнений целевого роута. Растёт только когда
     * middleware действительно пропускает запрос дальше по pipeline.
     */
    private static int $invocations = 0;

    protected function setUp(): void
    {
        parent::setUp();

        self::$invocations = 0;

        // Мутирующий тестовый роут под алиасом `idempotency`. Возвращает тело,
        // отражающее текущее значение счётчика, чтобы по содержимому ответа
        // можно было различить реплей от свежего выполнения.
        Route::middleware('idempotency')
            ->post('/idempotency-probe', static function () {
                self::$invocations++;

                return response()->json([
                    'ok' => true,
                    'invocations' => self::$invocations,
                ]);
            });
    }

    public function test_request_without_key_is_not_cached_and_runs_route_each_time(): void
    {
        // Требование 4.1: без заголовка Idempotency-Key каждый POST выполняет роут.
        $first = $this->postJson('/idempotency-probe', ['x' => 1]);
        $first->assertOk();
        $first->assertJsonPath('invocations', 1);

        $second = $this->postJson('/idempotency-probe', ['x' => 1]);
        $second->assertOk();
        $second->assertJsonPath('invocations', 2);

        $this->assertSame(2, self::$invocations);
    }

    public function test_repeated_request_with_same_key_and_body_replays_cached_response(): void
    {
        // Требования 4.3, 4.4: первый запрос выполняет роут и кэширует ответ;
        // повторный с тем же ключом и телом возвращает закэшированный ответ
        // БЕЗ повторного выполнения роута.
        $headers = ['Idempotency-Key' => 'key-replay-001'];

        $first = $this->postJson('/idempotency-probe', ['x' => 1], $headers);
        $first->assertOk();
        $first->assertJsonPath('invocations', 1);
        $this->assertSame(1, self::$invocations);
        $this->assertNull($first->headers->get(EnforceIdempotency::REPLAYED_HEADER));

        $second = $this->postJson('/idempotency-probe', ['x' => 1], $headers);
        $second->assertOk();
        // Тело идентично исходному — счётчик не увеличился (роут не вызван).
        $second->assertJsonPath('invocations', 1);
        $this->assertSame(1, self::$invocations);

        // Реплей помечен служебным заголовком.
        $this->assertSame('true', $second->headers->get(EnforceIdempotency::REPLAYED_HEADER));
    }

    public function test_same_key_with_different_body_returns_422_body_mismatch(): void
    {
        // Требование 4.6: тот же ключ, другое тело → 422 idempotency_body_mismatch.
        $headers = ['Idempotency-Key' => 'key-mismatch-001'];

        $this->postJson('/idempotency-probe', ['x' => 1], $headers)->assertOk();
        $this->assertSame(1, self::$invocations);

        $response = $this->postJson('/idempotency-probe', ['x' => 2], $headers);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'idempotency_body_mismatch');

        // Роут не вызывался повторно.
        $this->assertSame(1, self::$invocations);
    }

    public function test_empty_key_returns_400_invalid(): void
    {
        // Требование 4.7: пустой ключ → 400 idempotency_key_invalid.
        $response = $this->postJson('/idempotency-probe', ['x' => 1], [
            'Idempotency-Key' => '',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('error.code', 'idempotency_key_invalid');
        $this->assertSame(0, self::$invocations);
    }

    public function test_too_long_key_returns_400_invalid(): void
    {
        // Требование 4.7: ключ длиннее 255 символов → 400 idempotency_key_invalid.
        $response = $this->postJson('/idempotency-probe', ['x' => 1], [
            'Idempotency-Key' => str_repeat('a', 256),
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('error.code', 'idempotency_key_invalid');
        $this->assertSame(0, self::$invocations);
    }

    public function test_key_of_max_length_is_accepted(): void
    {
        // Граница: ровно 255 символов — допустимо, роут выполняется.
        $response = $this->postJson('/idempotency-probe', ['x' => 1], [
            'Idempotency-Key' => str_repeat('a', 255),
        ]);

        $response->assertOk();
        $this->assertSame(1, self::$invocations);
    }

    public function test_in_flight_marker_returns_409_conflict(): void
    {
        // Требование 4.5: ключ помечен in-flight → 409 idempotency_in_flight.
        // Тело запроса должно совпадать с body_hash маркера, иначе сработает 4.6.
        $key = 'key-inflight-001';
        $payload = ['x' => 1];
        $bodyHash = hash('sha256', json_encode($payload));

        // Неаутентифицированный запрос → client_id = 'anonymous'.
        $cacheKey = sprintf('idem:v1:anonymous:%s', $key);

        Cache::store(config('cache.default'))->put($cacheKey, [
            'state' => 'in-flight',
            'body_hash' => $bodyHash,
        ], 60);

        $response = $this->postJson('/idempotency-probe', $payload, [
            'Idempotency-Key' => $key,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'idempotency_in_flight');

        // Роут не вызывался — запрос отвергнут на уровне middleware.
        $this->assertSame(0, self::$invocations);
    }

    public function test_cache_key_uses_idem_v1_namespace(): void
    {
        // Требование 4.2: ключ кеша имеет форму idem:v1:{client_id}:{key}.
        $key = 'key-namespace-001';

        $this->postJson('/idempotency-probe', ['x' => 1], [
            'Idempotency-Key' => $key,
        ])->assertOk();

        $cacheKey = sprintf('idem:v1:anonymous:%s', $key);
        $record = Cache::store(config('cache.default'))->get($cacheKey);

        $this->assertIsArray($record);
        $this->assertSame('cached', $record['state']);
        $this->assertArrayHasKey('body_hash', $record);
    }
}
