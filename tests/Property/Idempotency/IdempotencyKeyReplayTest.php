<?php

declare(strict_types=1);

namespace Tests\Property\Idempotency;

use App\Http\Middleware\EnforceIdempotency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Property-based тест для correctness property P2 спеки microservices-foundation
 * (design.md, раздел 6).
 *
 * Feature: microservices-foundation, Property P2: idempotency-key returns identical response within TTL
 *
 * Свойство (для всех client_id, key, body):
 *   повторный POST с тем же (client_id, key, body) в пределах TTL → идентичный
 *   ответ (status, body, headers за исключением Date / X-Request-Id), причём
 *   целевой роут НЕ выполняется повторно. Тот же key с другим body → 422
 *   idempotency_body_mismatch без побочных эффектов. Пустой key или key длиннее
 *   255 символов → 400 idempotency_key_invalid. Один и тот же key у разных
 *   client_id не конфликтует (per-client изоляция).
 *
 * Проверяемое поведение реализовано в App\Http\Middleware\EnforceIdempotency
 * (route-алиас `idempotency`). Тест НЕ изменяет middleware; контрпример здесь —
 * это сигнал о баге в middleware.
 *
 * PBT реализован ручным циклом (≥100 итераций для основного свойства, ≥30 для
 * блоков 4.7) с фиксированным сидом mt_srand для воспроизводимости.
 *
 * **Validates: Requirements 4.2, 4.3, 4.4, 4.6, 4.7**
 */
class IdempotencyKeyReplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тестовый mutating-роут под алиасом `idempotency`.
     */
    private const ROUTE = '/idempotency-pbt-probe';

    /**
     * Алфавит допустимых символов значения Idempotency-Key ([A-Za-z0-9-]).
     */
    private const KEY_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-';

    /**
     * Счётчик фактических выполнений целевого роута. Растёт только когда
     * middleware действительно пропускает запрос дальше по pipeline.
     */
    private static int $invocations = 0;

    /**
     * Несколько Sanctum-пользователей для проверки per-client изоляции (4.2):
     * у разных client_id разное пространство имён ключа idem:v1:{client_id}:{key}.
     *
     * @var array<int, User>
     */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::$invocations = 0;
        $this->users = User::factory()->count(3)->create()->all();

        // Детерминированный по телу запроса ответ + статический счётчик реальных
        // выполнений. По телу ответа (echo + invocation) можно отличить реплей
        // (закэшированный ответ) от свежего выполнения роута.
        Route::middleware('idempotency')
            ->post(self::ROUTE, static function (Request $request) {
                self::$invocations++;

                return response()->json([
                    'ok' => true,
                    'invocation' => self::$invocations,
                    'echo' => $request->json()->all(),
                ]);
            });
    }

    /**
     * Основное свойство P2 (≥100 итераций): replay в пределах TTL + body-mismatch.
     *
     * Для каждой итерации (с ротацией client_id: anonymous / Sanctum разных юзеров):
     *  1. Первый POST(key, body) → роут выполнен (счётчик +1), ответ A, без replay-заголовка (4.3).
     *  2. Повтор POST(key, body) → роут НЕ выполнен, статус+тело+Content-Type идентичны A,
     *     заголовок Idempotency-Replayed: true (4.4).
     *  3. POST(key, другой body) → 422 idempotency_body_mismatch, роут не выполнен (4.6).
     */
    public function test_property_replay_is_identical_and_body_mismatch_is_rejected(): void
    {
        mt_srand(20240607);

        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Ротация client_id: 0 — anonymous, 1..3 — Sanctum-пользователи.
            $this->resetAuth();
            $profile = $i % 4;
            if ($profile !== 0) {
                Sanctum::actingAs($this->users[$profile - 1]);
            }

            $key = $this->randomValidKey($i);
            $body = $this->randomBody();
            $headers = [EnforceIdempotency::HEADER => $key];

            $before = self::$invocations;

            // (1) Первый запрос: роут выполняется, ответ кэшируется (4.3).
            $first = $this->postJson(self::ROUTE, $body, $headers);
            $first->assertOk();
            $this->assertSame(
                $before + 1,
                self::$invocations,
                "Итерация {$i}: первый запрос должен выполнить роут",
            );
            $this->assertNull(
                $first->headers->get(EnforceIdempotency::REPLAYED_HEADER),
                "Итерация {$i}: первый ответ не должен быть помечен как реплей",
            );

            // (2) Повтор того же (key, body): закэшированный ответ без роута (4.4).
            $replay = $this->postJson(self::ROUTE, $body, $headers);
            $replay->assertOk();
            $this->assertSame(
                $before + 1,
                self::$invocations,
                "Итерация {$i}: повтор не должен выполнять роут",
            );
            $this->assertSame(
                $first->getStatusCode(),
                $replay->getStatusCode(),
                "Итерация {$i}: статус реплея должен совпадать с исходным",
            );
            $this->assertSame(
                $first->getContent(),
                $replay->getContent(),
                "Итерация {$i}: тело реплея должно быть идентично исходному",
            );
            $this->assertSame(
                $first->headers->get('Content-Type'),
                $replay->headers->get('Content-Type'),
                "Итерация {$i}: Content-Type реплея должен совпадать с исходным",
            );
            $this->assertSame(
                'true',
                $replay->headers->get(EnforceIdempotency::REPLAYED_HEADER),
                "Итерация {$i}: реплей должен помечаться Idempotency-Replayed: true",
            );

            // (3) Тот же key, другое тело → 422 idempotency_body_mismatch (4.6).
            $differentBody = $this->mutateBody($body);
            $mismatch = $this->postJson(self::ROUTE, $differentBody, $headers);
            $mismatch->assertStatus(422);
            $mismatch->assertJsonPath('error.code', 'idempotency_body_mismatch');
            $this->assertSame(
                $before + 1,
                self::$invocations,
                "Итерация {$i}: body-mismatch не должен выполнять роут",
            );
        }
    }

    /**
     * Per-client изоляция (4.2): один и тот же (key, body) у разных client_id не
     * конфликтует — каждый client выполняет роут независимо, реплей не срабатывает.
     *
     * Покрывает anonymous и нескольких Sanctum-пользователей.
     */
    public function test_property_same_key_is_isolated_per_client(): void
    {
        mt_srand(987654321);

        $iterations = 50;

        for ($i = 0; $i < $iterations; $i++) {
            $key = $this->randomValidKey($i);
            $body = $this->randomBody();
            $headers = [EnforceIdempotency::HEADER => $key];

            $before = self::$invocations;

            // Клиент A — anonymous.
            $this->resetAuth();
            $responseAnon = $this->postJson(self::ROUTE, $body, $headers);
            $responseAnon->assertOk();
            $this->assertNull(
                $responseAnon->headers->get(EnforceIdempotency::REPLAYED_HEADER),
                "Итерация {$i}: anonymous-запрос должен выполнить роут, а не реплей",
            );

            // Клиент B — Sanctum-пользователь #1: тот же key+body, другой client_id.
            $this->resetAuth();
            Sanctum::actingAs($this->users[0]);
            $responseUser1 = $this->postJson(self::ROUTE, $body, $headers);
            $responseUser1->assertOk();
            $this->assertNull(
                $responseUser1->headers->get(EnforceIdempotency::REPLAYED_HEADER),
                "Итерация {$i}: user#1 не должен получить чужой закэшированный ответ",
            );

            // Клиент C — Sanctum-пользователь #2: снова тот же key+body.
            $this->resetAuth();
            Sanctum::actingAs($this->users[1]);
            $responseUser2 = $this->postJson(self::ROUTE, $body, $headers);
            $responseUser2->assertOk();
            $this->assertNull(
                $responseUser2->headers->get(EnforceIdempotency::REPLAYED_HEADER),
                "Итерация {$i}: user#2 не должен получить чужой закэшированный ответ",
            );

            // Три разных client_id → три независимых выполнения роута.
            $this->assertSame(
                $before + 3,
                self::$invocations,
                "Итерация {$i}: один key у трёх client_id должен выполниться трижды",
            );
        }
    }

    /**
     * 4.7: пустой Idempotency-Key → 400 idempotency_key_invalid, роут не выполняется.
     */
    public function test_property_empty_key_is_rejected_with_400(): void
    {
        mt_srand(111222333);

        $iterations = 40;

        for ($i = 0; $i < $iterations; $i++) {
            $this->resetAuth();
            $body = $this->randomBody();

            $before = self::$invocations;

            $response = $this->postJson(self::ROUTE, $body, [
                EnforceIdempotency::HEADER => '',
            ]);

            $response->assertStatus(400);
            $response->assertJsonPath('error.code', 'idempotency_key_invalid');
            $this->assertSame(
                $before,
                self::$invocations,
                "Итерация {$i}: пустой key не должен выполнять роут",
            );
        }
    }

    /**
     * 4.7: Idempotency-Key длиной 256..400 символов → 400 idempotency_key_invalid,
     * роут не выполняется.
     */
    public function test_property_too_long_key_is_rejected_with_400(): void
    {
        mt_srand(444555666);

        $iterations = 40;

        for ($i = 0; $i < $iterations; $i++) {
            $this->resetAuth();
            $body = $this->randomBody();
            $key = $this->randomKeyString(mt_rand(256, 400));

            $before = self::$invocations;

            $response = $this->postJson(self::ROUTE, $body, [
                EnforceIdempotency::HEADER => $key,
            ]);

            $response->assertStatus(400);
            $response->assertJsonPath('error.code', 'idempotency_key_invalid');
            $this->assertSame(
                $before,
                self::$invocations,
                "Итерация {$i}: слишком длинный key не должен выполнять роут",
            );
        }
    }

    /**
     * Сбрасывает аутентификацию между запросами, чтобы корректно переключаться
     * между anonymous и разными Sanctum-пользователями в пределах одного теста.
     */
    private function resetAuth(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('oauth_client_id');
    }

    /**
     * Генерирует валидный Idempotency-Key длиной 1..255 из алфавита [A-Za-z0-9-].
     * Префикс с индексом итерации гарантирует уникальность ключа между итерациями,
     * чтобы кеш не пересекался.
     */
    private function randomValidKey(int $iteration): string
    {
        $prefix = 'it'.$iteration.'-';
        $maxRandomLength = 255 - strlen($prefix);
        $length = mt_rand(1, $maxRandomLength);

        return $prefix.$this->randomKeyString($length);
    }

    /**
     * Случайная строка заданной длины из алфавита допустимых символов ключа.
     */
    private function randomKeyString(int $length): string
    {
        $alphabetMax = strlen(self::KEY_ALPHABET) - 1;
        $key = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= self::KEY_ALPHABET[mt_rand(0, $alphabetMax)];
        }

        return $key;
    }

    /**
     * Случайное тело запроса (JSON-объект). Поле `seed` присутствует всегда, что
     * гарантирует непустое детерминированно отличаемое тело.
     *
     * @return array<string, mixed>
     */
    private function randomBody(): array
    {
        $body = ['seed' => mt_rand(1, 1_000_000)];

        $extra = mt_rand(0, 4);
        for ($i = 0; $i < $extra; $i++) {
            $body['f'.$i] = $this->randomScalar();
        }

        return $body;
    }

    /**
     * Возвращает тело, гарантированно отличающееся по raw-содержимому (другой
     * sha256), меняя значение всегда присутствующего поля `seed`.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function mutateBody(array $body): array
    {
        $body['seed'] = ((int) $body['seed']) + 1;

        return $body;
    }

    /**
     * Случайный скаляр одного из базовых JSON-типов.
     */
    private function randomScalar(): int|string|bool|float
    {
        return match (mt_rand(0, 3)) {
            0 => mt_rand(-1000, 1000),
            1 => $this->randomKeyString(mt_rand(0, 12)),
            2 => (bool) mt_rand(0, 1),
            default => mt_rand(0, 100000) / 100,
        };
    }
}
