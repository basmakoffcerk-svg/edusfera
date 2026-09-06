<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnforceServiceScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Unit-тесты для EnforceServiceScope middleware.
 *
 * Покрывает требования 13.2, 13.3, 13.4 спеки microservices-foundation:
 *  - Sanctum-токен с нужным scope → 200 (пропускает)
 *  - Sanctum-токен без нужного scope → 403 с error.code = "insufficient_scope"
 *  - Неаутентифицированный запрос → 403
 *  - Формат JSON-ответа 403
 */
class EnforceServiceScopeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Регистрирует тестовый роут с middleware scope:{required}.
     */
    private function registerScopeRoute(string $required, string $uri = '/test-scope-probe'): void
    {
        Route::middleware(['api', "scope:{$required}"])
            ->get($uri, fn () => response()->json(['ok' => true]));
    }

    /**
     * Создаёт фейкового пользователя с Sanctum-токеном, имеющим указанные abilities.
     *
     * @param  string[]  $abilities
     */
    private function actingWithSanctumToken(array $abilities): static
    {
        $user = \App\Models\User::factory()->make(['id' => 1]);

        // Sanctum использует HasApiTokens::tokenCan() — мокаем через
        // actingAs с указанием abilities (второй аргумент).
        return $this->actingAs($user, 'sanctum');
    }

    // -------------------------------------------------------------------------
    // Тест 1: Sanctum-токен с нужным scope → 200
    // -------------------------------------------------------------------------

    public function test_allows_request_when_sanctum_token_has_required_scope(): void
    {
        $this->registerScopeRoute('lessons:read');

        $user = \App\Models\User::factory()->make(['id' => 1]);

        // actingAs с abilities — Sanctum проверяет их через tokenCan()
        $this->actingAs($user, 'sanctum');

        // Мокаем tokenCan через частичный мок пользователя
        $mockUser = $this->createMockUserWithScope('lessons:read', hasScope: true);

        $response = $this->withoutMiddleware(\App\Http\Middleware\AssignRequestId::class)
            ->actingAs($mockUser, 'sanctum')
            ->getJson('/test-scope-probe');

        $response->assertOk()
            ->assertJson(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Тест 2: Sanctum-токен без нужного scope → 403
    // -------------------------------------------------------------------------

    public function test_denies_request_when_sanctum_token_lacks_required_scope(): void
    {
        $this->registerScopeRoute('lessons:write', '/test-scope-probe-2');

        $mockUser = $this->createMockUserWithScope('lessons:write', hasScope: false);

        $response = $this->actingAs($mockUser, 'sanctum')
            ->getJson('/test-scope-probe-2');

        $response->assertForbidden();
        $response->assertJsonPath('error.code', 'insufficient_scope');
    }

    // -------------------------------------------------------------------------
    // Тест 3: Неаутентифицированный запрос → 403
    // -------------------------------------------------------------------------

    public function test_denies_unauthenticated_request(): void
    {
        $this->registerScopeRoute('lessons:read', '/test-scope-probe-3');

        $response = $this->getJson('/test-scope-probe-3');

        // Без аутентификации scope не может быть проверен → 403
        $response->assertForbidden();
        $response->assertJsonPath('error.code', 'insufficient_scope');
    }

    // -------------------------------------------------------------------------
    // Тест 4: Формат JSON-ответа 403
    // -------------------------------------------------------------------------

    public function test_403_response_has_correct_json_structure(): void
    {
        $this->registerScopeRoute('ai:invoke', '/test-scope-probe-4');

        $mockUser = $this->createMockUserWithScope('ai:invoke', hasScope: false);

        $response = $this->actingAs($mockUser, 'sanctum')
            ->getJson('/test-scope-probe-4');

        $response->assertForbidden();
        $response->assertJsonStructure([
            'error' => [
                'code',
                'message',
                'request_id',
            ],
        ]);
        $response->assertJsonPath('error.code', 'insufficient_scope');
        $this->assertIsString($response->json('error.message'));
        $this->assertNotEmpty($response->json('error.message'));
        // request_id может быть пустой строкой (если AssignRequestId не запущен),
        // но ключ должен присутствовать
        $this->assertArrayHasKey('request_id', $response->json('error'));
    }

    // -------------------------------------------------------------------------
    // Тест 5: Middleware зарегистрирован как алиас `scope`
    // -------------------------------------------------------------------------

    public function test_scope_alias_is_registered_in_router(): void
    {
        $aliases = app('router')->getMiddleware();

        $this->assertArrayHasKey(
            'scope',
            $aliases,
            'Алиас scope должен быть зарегистрирован в router middleware',
        );
        $this->assertSame(
            EnforceServiceScope::class,
            $aliases['scope'],
        );
    }

    // -------------------------------------------------------------------------
    // Тест 6: Прямой вызов middleware — разрешено
    // -------------------------------------------------------------------------

    public function test_middleware_passes_request_with_matching_oauth_scopes_attribute(): void
    {
        $middleware = new EnforceServiceScope;

        $request = Request::create('/test', 'GET');
        // Имитируем Passport client_credentials: проставляем oauth_scopes в attributes
        $request->attributes->set('oauth_scopes', ['lessons:read', 'homework:read']);

        // Нужен аутентифицированный пользователь
        $user = \App\Models\User::factory()->make(['id' => 2]);
        $request->setUserResolver(fn () => $user);

        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;

            return response()->json(['ok' => true]);
        };

        $response = $middleware($request, $next, 'lessons:read');

        $this->assertTrue($called, 'Next должен быть вызван при наличии scope');
        $this->assertSame(200, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Тест 7: Прямой вызов middleware — запрещено (oauth_scopes не содержит нужный)
    // -------------------------------------------------------------------------

    public function test_middleware_blocks_request_when_oauth_scopes_attribute_lacks_required(): void
    {
        $middleware = new EnforceServiceScope;

        $request = Request::create('/test', 'GET');
        $request->attributes->set('oauth_scopes', ['homework:read']);

        $user = \App\Models\User::factory()->make(['id' => 3]);
        $request->setUserResolver(fn () => $user);

        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;

            return response()->json(['ok' => true]);
        };

        $response = $middleware($request, $next, 'lessons:write');

        $this->assertFalse($called, 'Next не должен быть вызван при отсутствии scope');
        $this->assertSame(403, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertSame('insufficient_scope', $body['error']['code']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Создаёт мок пользователя, у которого tokenCan($scope) возвращает $hasScope.
     */
    private function createMockUserWithScope(string $scope, bool $hasScope): \App\Models\User
    {
        $user = $this->createPartialMock(\App\Models\User::class, ['tokenCan']);
        $user->method('tokenCan')
            ->with($scope)
            ->willReturn($hasScope);

        return $user;
    }
}
