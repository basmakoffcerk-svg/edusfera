<?php

declare(strict_types=1);

namespace Tests\Property\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Property-based тест для correctness property P7 (design.md, раздел 6) —
 * принудительная проверка scope в EnforceServiceScope middleware (алиас `scope`).
 *
 * Feature: microservices-foundation, Property P7: service-token scope enforcement
 *
 * Свойство формулируется так (квантор по всем парам (требуемый scope, scope-ы токена)):
 *
 *   ∀ запрос R на эндпоинт E, защищённый middleware `scope:S`, c токеном T:
 *       allow(R)  ⟺  S ∈ T.scopes
 *       ¬allow(R) ⟹  response(R).status = 403  ∧  error.code = "insufficient_scope"
 *
 * То есть allow тогда и только тогда, когда требуемый scope входит в набор
 * scope-ов токена; в противном случае — 403 без выполнения роута (требования
 * 13.2, 13.3, 13.4).
 *
 * Эмуляция scope-ов токена детерминирована и без реального Passport/OAuth:
 * используется `Sanctum::actingAs($user, $tokenScopes)`. Sanctum строит mock
 * personal access token, у которого `can($ability)` возвращает true ровно для
 * abilities из списка (а для остальных — false, т.к. mock создаётся через
 * `shouldIgnoreMissing(false)`). Middleware EnforceServiceScope проверяет
 * Sanctum-токен через `$user->tokenCan($required)`, поэтому
 * `tokenCan(S) === (S ∈ tokenScopes)`. В генерации НЕ используется ability `*`
 * (только конкретные scope из каталога), иначе tokenCan стал бы тотально true.
 *
 * Каталог scope-ов берётся из `config('oauth.scopes')` (требование 13.1).
 * В setUp для каждого scope регистрируется отдельный роут под middleware
 * `['api', "scope:$scope"]` с уникальным URI; в каждой итерации выбирается
 * нужный requiredScope и выполняется реальный HTTP-запрос на соответствующий URI.
 *
 * Все генераторы детерминированы через mt_srand с фиксированным сидом.
 *
 * **Validates: Requirements 13.2, 13.3, 13.4**
 */
class ServiceScopeEnforcementTest extends TestCase
{
    use RefreshDatabase;

    /** Число итераций основного двустороннего свойства (≥100). */
    private const ITERATIONS_MAIN = 200;

    /** Зафиксированный сид для детерминированной генерации. */
    private const SEED = 70707;

    /** @var list<string> Каталог scope-ов из config('oauth.scopes'). */
    private array $catalog = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalog = array_keys(config('oauth.scopes'));

        // Для каждого scope каталога — отдельный тестовый роут под middleware
        // `scope:$scope`. Параметр middleware фиксируется при регистрации, поэтому
        // на каждый required scope нужен свой URI.
        foreach ($this->catalog as $scope) {
            Route::middleware(['api', "scope:{$scope}"])
                ->get($this->uriFor($scope), fn () => response()->json(['ok' => true]));
        }
    }

    /**
     * Основное свойство P7: allow ⟺ requiredScope ∈ tokenScopes.
     *
     * В каждой итерации генерируется произвольное подмножество каталога как
     * scope-ы токена и произвольный required scope. Запрос на роут `scope:$required`
     * должен пройти (200) тогда и только тогда, когда required входит в scope-ы
     * токена; иначе — 403 с error.code = "insufficient_scope".
     */
    public function test_allow_iff_required_scope_in_token_scopes(): void
    {
        mt_srand(self::SEED);

        $sawAllow = false;
        $sawDeny = false;

        for ($i = 0; $i < self::ITERATIONS_MAIN; $i++) {
            $tokenScopes = $this->randomScopeSubset();
            $requiredScope = $this->catalog[mt_rand(0, count($this->catalog) - 1)];

            $expectedAllow = in_array($requiredScope, $tokenScopes, strict: true);

            $context = sprintf(
                'i=%d required=%s tokenScopes=[%s] expectedAllow=%s',
                $i,
                $requiredScope,
                implode(',', $tokenScopes),
                $expectedAllow ? 'true' : 'false',
            );

            $response = $this->actingWithScopes($tokenScopes)
                ->getJson($this->uriFor($requiredScope));

            if ($expectedAllow) {
                $response->assertOk($context);
                $response->assertJson(['ok' => true]);
                $sawAllow = true;
            } else {
                $response->assertForbidden();
                $response->assertJsonPath('error.code', 'insufficient_scope');
                $sawDeny = true;
            }
        }

        // Двусторонняя проверка покрыта обоими исходами в выборке.
        $this->assertTrue($sawAllow, 'Ожидался хотя бы один allow-кейс за прогон');
        $this->assertTrue($sawDeny, 'Ожидался хотя бы один deny-кейс за прогон');
    }

    /**
     * Граничный случай: пустой набор scope-ов токена → любой required scope
     * отвергается с 403 insufficient_scope (нет scope ⟹ нет доступа).
     */
    public function test_empty_token_scopes_always_denied(): void
    {
        mt_srand(self::SEED + 1);

        foreach ($this->catalog as $requiredScope) {
            $response = $this->actingWithScopes([])
                ->getJson($this->uriFor($requiredScope));

            $response->assertForbidden();
            $response->assertJsonPath('error.code', 'insufficient_scope');
        }
    }

    /**
     * Граничный случай: токен с полным каталогом scope-ов → любой required scope
     * пропускается (200) — полный набор прав покрывает все роуты.
     */
    public function test_full_catalog_token_scopes_always_allowed(): void
    {
        mt_srand(self::SEED + 2);

        foreach ($this->catalog as $requiredScope) {
            $response = $this->actingWithScopes($this->catalog)
                ->getJson($this->uriFor($requiredScope));

            $response->assertOk();
            $response->assertJson(['ok' => true]);
        }
    }

    /**
     * Аутентифицирует пользователя Sanctum-токеном с заданными abilities (scope-ами).
     *
     * `Sanctum::actingAs` создаёт mock-токен, у которого `can($s)` возвращает true
     * ровно для $scopes; EnforceServiceScope проверяет это через `$user->tokenCan()`.
     *
     * @param  list<string>  $scopes
     */
    private function actingWithScopes(array $scopes): static
    {
        $user = User::factory()->make(['id' => mt_rand(1, 1_000_000)]);

        Sanctum::actingAs($user, $scopes);

        return $this;
    }

    /**
     * Случайное подмножество каталога scope-ов (каждый включается с вероятностью 50%).
     * Диапазон — от пустого набора до полного каталога. Ability `*` НЕ используется.
     *
     * @return list<string>
     */
    private function randomScopeSubset(): array
    {
        $subset = [];

        foreach ($this->catalog as $scope) {
            if (mt_rand(0, 1) === 1) {
                $subset[] = $scope;
            }
        }

        return $subset;
    }

    /**
     * URI тестового роута для конкретного scope (`:` → `_`, чтобы получить валидный путь).
     */
    private function uriFor(string $scope): string
    {
        return '/scope-pbt/'.str_replace(':', '_', $scope);
    }
}
