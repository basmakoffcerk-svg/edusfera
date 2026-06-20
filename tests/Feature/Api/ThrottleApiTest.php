<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Покрывает требование 17 спеки microservices-foundation (throttle policy api.v1):
 *  - 17.1: rate-limiter `api.v1` зарегистрирован.
 *  - 17.2: 60/min на user_id для Sanctum-пользователя (в тесте лимит понижен).
 *  - 17.4: при превышении — 429 c заголовком `Retry-After`.
 *  - 17.5: заголовки `X-RateLimit-Limit` и `X-RateLimit-Remaining` на успешных ответах.
 *
 * Лимиты детерминированно понижаются через config('api.throttle.*'), который
 * читается limiter-замыканием на каждый запрос. Счётчики живут в array-cache
 * (CACHE_STORE=array) и сбрасываются между тест-методами вместе с контейнером.
 */
class ThrottleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Временный роут под тем же стеком, что и боевая группа api.v1:
        // группа `api` (AssignRequestId → StructuredLogging → …) + throttle:api.v1.
        Route::middleware(['api', 'throttle:'.RouteServiceProvider::API_V1_LIMITER])
            ->prefix('api/v1')
            ->group(function (): void {
                Route::get('/throttle-probe', static function () {
                    return response()->json(['ok' => true]);
                });
            });
    }

    public function test_successful_response_includes_rate_limit_headers(): void
    {
        config(['api.throttle.ip' => 5]);

        $response = $this->getJson('/api/v1/throttle-probe');

        $response->assertOk();

        // Требование 17.5: оба заголовка присутствуют на успешном ответе.
        $this->assertSame('5', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('4', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_remaining_header_decrements_on_each_request(): void
    {
        config(['api.throttle.ip' => 5]);

        $first = $this->getJson('/api/v1/throttle-probe');
        $first->assertOk();
        $this->assertSame('4', $first->headers->get('X-RateLimit-Remaining'));

        $second = $this->getJson('/api/v1/throttle-probe');
        $second->assertOk();
        $this->assertSame('3', $second->headers->get('X-RateLimit-Remaining'));
    }

    public function test_returns_429_with_retry_after_when_ip_limit_exceeded(): void
    {
        // Fallback по IP для неаутентифицированного запроса (лимит = 2).
        config(['api.throttle.ip' => 2]);

        $this->getJson('/api/v1/throttle-probe')->assertOk();
        $this->getJson('/api/v1/throttle-probe')->assertOk();

        // Третий запрос превышает лимит (требование 17.4).
        $response = $this->getJson('/api/v1/throttle-probe');

        $response->assertStatus(429);
        $this->assertNotNull(
            $response->headers->get('Retry-After'),
            'Ответ 429 должен содержать заголовок Retry-After',
        );

        // 429 возвращается в едином конверте ошибки с error.code = "rate_limited".
        $response->assertJsonPath('error.code', 'rate_limited');
    }

    public function test_authenticated_user_is_throttled_per_user_id(): void
    {
        // Требование 17.2: лимит на user_id (в тесте понижен до 2).
        config(['api.throttle.user' => 2]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $first = $this->getJson('/api/v1/throttle-probe');
        $first->assertOk();
        // Лимит на success-ответе равен пользовательскому, а не IP-fallback.
        $this->assertSame('2', $first->headers->get('X-RateLimit-Limit'));

        $this->getJson('/api/v1/throttle-probe')->assertOk();

        $this->getJson('/api/v1/throttle-probe')->assertStatus(429);
    }

    public function test_limiter_is_registered(): void
    {
        // Требование 17.1: rate-limiter `api.v1` определён.
        $limiter = app(\Illuminate\Cache\RateLimiter::class)
            ->limiter(RouteServiceProvider::API_V1_LIMITER);

        $this->assertNotNull(
            $limiter,
            'Rate-limiter api.v1 должен быть зарегистрирован в RouteServiceProvider',
        );
    }
}
