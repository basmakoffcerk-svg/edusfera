<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Http\Middleware\AssignRequestId;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Покрывает требования 5.1, 5.2, 5.3, 5.6 microservices-foundation:
 * Request-Id propagation и корректную регистрацию AssignRequestId в группе api.
 */
class AssignRequestIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Тестовый роут под группой `api`, чтобы реально прогнать middleware-стек.
        Route::middleware('api')->get('/test-request-id-probe', function () {
            return response()->json([
                'container' => app('request_id'),
                'header_seen' => request()->headers->get(AssignRequestId::HEADER),
                'log_context' => Log::sharedContext()['request_id'] ?? null,
            ]);
        });
    }

    public function test_generates_uuid_v7_when_no_header_present(): void
    {
        $response = $this->getJson('/test-request-id-probe');

        $response->assertOk();

        $generated = $response->headers->get(AssignRequestId::HEADER);

        $this->assertNotNull($generated, 'X-Request-Id должен быть выставлен на ответ');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $generated,
            'Сгенерированный request_id должен быть UUID v7',
        );

        $payload = $response->json();
        $this->assertSame($generated, $payload['container']);
        $this->assertSame($generated, $payload['header_seen']);
        $this->assertSame($generated, $payload['log_context']);
    }

    public function test_accepts_valid_uuid_v4_from_header(): void
    {
        $incoming = '550e8400-e29b-41d4-a716-446655440000'; // классический UUID v4

        $response = $this->getJson('/test-request-id-probe', [
            AssignRequestId::HEADER => $incoming,
        ]);

        $response->assertOk();
        $this->assertSame($incoming, $response->headers->get(AssignRequestId::HEADER));
        $this->assertSame($incoming, $response->json('container'));
        $this->assertSame($incoming, $response->json('log_context'));
    }

    public function test_accepts_valid_uuid_v7_from_header(): void
    {
        // UUID v7: версия = 7, вариант = 8/9/a/b
        $incoming = '018f2c5b-4d0e-7a3f-9b21-2a4e1f7a9d10';

        $response = $this->getJson('/test-request-id-probe', [
            AssignRequestId::HEADER => $incoming,
        ]);

        $response->assertOk();
        $this->assertSame($incoming, $response->headers->get(AssignRequestId::HEADER));
        $this->assertSame($incoming, $response->json('container'));
    }

    public function test_replaces_invalid_header_with_generated_uuid(): void
    {
        $invalid = 'not-a-real-uuid';

        $response = $this->getJson('/test-request-id-probe', [
            AssignRequestId::HEADER => $invalid,
        ]);

        $response->assertOk();

        $resolved = $response->headers->get(AssignRequestId::HEADER);
        $this->assertNotSame($invalid, $resolved, 'Невалидный заголовок должен быть отброшен');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $resolved,
        );
    }

    public function test_rejects_uuid_with_invalid_version_nibble(): void
    {
        // Технически валидный UUID, но версия v3 — не v4 и не v7, отвергаем.
        $v3 = '6fa459ea-ee8a-3ca4-894e-db77e160355e';

        $response = $this->getJson('/test-request-id-probe', [
            AssignRequestId::HEADER => $v3,
        ]);

        $response->assertOk();

        $resolved = $response->headers->get(AssignRequestId::HEADER);
        $this->assertNotSame($v3, $resolved, 'UUID версий помимо v4/v7 не должны приниматься');
    }

    public function test_middleware_is_registered_in_api_group(): void
    {
        // Требование 5.6: middleware зарегистрирован в стеке `api` через bootstrap/app.php.
        $apiMiddleware = app('router')->getMiddlewareGroups()['api'] ?? [];

        $this->assertContains(
            AssignRequestId::class,
            $apiMiddleware,
            'AssignRequestId должен быть зарегистрирован в middleware-группе api',
        );
    }
}
