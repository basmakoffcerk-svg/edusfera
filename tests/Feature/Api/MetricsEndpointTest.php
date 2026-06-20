<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Покрывает эндпоинт `GET /metrics` спеки microservices-foundation (требование 16):
 *  - 16.2: эндпоинт отдаёт метрики в Prometheus exposition format.
 *  - 16.3: без валидного scope `internal:metrics:read` / basic-auth → 401.
 *  - 16.4: экспортируются counter `http_requests_total` и gauge `outbox_pending_total`;
 *          gauge отражает число неопубликованных строк в `integration_outbox`.
 *  - 16.5: метрики HTTP-запросов агрегируются middleware-обёрткой.
 */
class MetricsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_without_token_when_protection_is_scope(): void
    {
        // Требование 16.3: protection=scope (дефолт) и отсутствие токена → 401.
        config(['metrics.protection' => 'scope']);

        $response = $this->get('/metrics');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_returns_200_and_exposition_format_with_valid_scope(): void
    {
        // Требования 16.2, 16.3: валидный scope `internal:metrics:read` → 200,
        // тело в формате Prometheus exposition (содержит # HELP / # TYPE).
        config(['metrics.protection' => 'scope']);

        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user, ['internal:metrics:read']);

        $response = $this->get('/metrics');

        $response->assertOk();
        $this->assertStringContainsString(
            'text/plain; version=0.0.4',
            (string) $response->headers->get('Content-Type'),
        );

        $body = $response->getContent();
        $this->assertStringContainsString('# HELP', $body);
        $this->assertStringContainsString('# TYPE', $body);
    }

    public function test_returns_401_with_token_lacking_required_scope(): void
    {
        // Требование 16.3: токен без scope `internal:metrics:read` → 401.
        config(['metrics.protection' => 'scope']);

        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user, ['lessons:read']);

        $response = $this->get('/metrics');

        $response->assertStatus(401);
    }

    public function test_basic_auth_protection_allows_valid_credentials(): void
    {
        // Требование 16.3: protection=basic_auth, валидные креды → 200.
        config([
            'metrics.protection' => 'basic_auth',
            'metrics.basic_auth.user' => 'prometheus',
            'metrics.basic_auth.password' => 's3cret',
        ]);

        $credentials = base64_encode('prometheus:s3cret');

        $response = $this->get('/metrics', ['Authorization' => 'Basic '.$credentials]);

        $response->assertOk();
    }

    public function test_basic_auth_protection_rejects_invalid_credentials(): void
    {
        // Требование 16.3: protection=basic_auth, неверные креды → 401.
        config([
            'metrics.protection' => 'basic_auth',
            'metrics.basic_auth.user' => 'prometheus',
            'metrics.basic_auth.password' => 's3cret',
        ]);

        $credentials = base64_encode('prometheus:wrong');

        $response = $this->get('/metrics', ['Authorization' => 'Basic '.$credentials]);

        $response->assertStatus(401);
    }

    public function test_http_requests_total_counter_is_recorded(): void
    {
        // Требования 16.4, 16.5: после обработки запроса counter
        // `http_requests_total` присутствует в экспорте.
        // Счётчик пишется в terminate() ПОСЛЕ ответа, поэтому первый запрос
        // регистрирует метрику, а второй уже видит её в теле.
        config(['metrics.protection' => 'scope']);

        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user, ['internal:metrics:read']);

        $this->get('/metrics')->assertOk();

        $body = $this->get('/metrics')->getContent();

        $this->assertStringContainsString('edusfera_http_requests_total', $body);
    }

    public function test_outbox_pending_gauge_reflects_unpublished_rows(): void
    {
        // Требование 16.4: gauge `outbox_pending_total` отражает число строк
        // в integration_outbox с published_at IS NULL.
        config(['metrics.protection' => 'scope']);

        $this->insertOutboxRow(publishedAt: null);
        $this->insertOutboxRow(publishedAt: null);
        // Опубликованная строка не должна попасть в счётчик pending.
        $this->insertOutboxRow(publishedAt: now()->toDateTimeString());

        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user, ['internal:metrics:read']);

        $body = $this->get('/metrics')->assertOk()->getContent();

        $this->assertStringContainsString('edusfera_outbox_pending_total', $body);
        // Две неопубликованные строки → gauge = 2.
        $this->assertMatchesRegularExpression(
            '/edusfera_outbox_pending_total\s+2\b/',
            $body,
        );
    }

    /**
     * Вставляет минимальную валидную строку в integration_outbox.
     */
    private function insertOutboxRow(?string $publishedAt): void
    {
        $now = now()->toDateTimeString();

        DB::table('integration_outbox')->insert([
            'id' => (string) Str::uuid7(),
            'event_type' => 'lesson.completed.v1',
            'aggregate_type' => 'lesson',
            'aggregate_id' => (string) random_int(1, 100000),
            'payload' => json_encode(['lesson_id' => 1], JSON_THROW_ON_ERROR),
            'occurred_at' => $now,
            'available_at' => $now,
            'published_at' => $publishedAt,
            'attempts' => 0,
            'last_error' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
