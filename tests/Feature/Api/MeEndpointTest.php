<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Покрывает smoke-эндпоинт `GET /api/v1/me` спеки microservices-foundation:
 *  - 1.5: эндпоинт возвращает 200 с информацией об аутентифицированном субъекте
 *         при наличии валидного токена.
 *  - 2.3: при валидном Sanctum-токене аутентифицированный пользователь
 *         устанавливается в request context.
 *  - 2.4: без валидного токена роут под `auth:sanctum` возвращает 401.
 *
 * Используются реальные personal access token'ы Sanctum (User::createToken),
 * чтобы проверять настоящий путь чтения abilities через
 * currentAccessToken()->abilities, без подмены мок-объектами.
 */
class MeEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_without_token(): void
    {
        // Требование 2.4: без валидного токена защищённый роут отвергает запрос.
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    public function test_returns_200_with_valid_sanctum_token(): void
    {
        // Требования 1.5, 2.3: валидный Sanctum-токен → 200 с данными субъекта.
        $user = User::factory()->create(['role' => 'tutor']);

        $token = $user->createToken('test', ['lessons:read'])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/me');

        $response->assertOk();
        $response->assertJsonPath('user_id', $user->id);
        $response->assertJsonPath('role', 'tutor');
        $response->assertJsonPath('scopes', ['lessons:read']);
    }

    public function test_returns_200_with_multiple_abilities(): void
    {
        // Несколько abilities корректно отражаются в scopes.
        $user = User::factory()->create(['role' => 'student']);

        $token = $user->createToken('test', ['lessons:read', 'homework:write'])->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/me');

        $response->assertOk();
        $response->assertJsonPath('role', 'student');
        $response->assertJsonPath('scopes', ['lessons:read', 'homework:write']);
    }

    public function test_response_echoes_request_id_header(): void
    {
        // request_id присутствует в теле ответа и согласован с заголовком X-Request-Id.
        $user = User::factory()->create(['role' => 'admin']);

        $token = $user->createToken('test', ['lessons:read'])->plainTextToken;

        $requestId = '0192b8e0-7c4a-7000-8000-000000000abc';

        $response = $this->withToken($token)
            ->getJson('/api/v1/me', ['X-Request-Id' => $requestId]);

        $response->assertOk();
        $response->assertJsonPath('request_id', $requestId);
        $this->assertSame($requestId, $response->headers->get('X-Request-Id'));
    }
}
