<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Contracts\Classroom\ClassroomTokenIssuer;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Покрывает JWKS endpoint `GET /api/v1/.well-known/jwks.json`
 * спеки microservices-foundation:
 *
 *  - 3.4: публичный эндпоинт возвращает JWK Set с текущим публичным ключом RS256.
 *  - 3.7: поддержка ротации — при наличии предыдущего ключа оба публикуются.
 *
 * Тесты используют реальный RSA-ключ из storage/ (сгенерирован при установке),
 * без мок-объектов, чтобы проверять настоящую конвертацию PEM → JWK.
 */
class JwksEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Требование 3.4: GET /api/v1/.well-known/jwks.json возвращает 200
     * без аутентификации (публичный эндпоинт).
     */
    public function test_returns_200_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/.well-known/jwks.json');

        $response->assertStatus(200);
    }

    /**
     * Требование 3.4: ответ содержит массив `keys`.
     */
    public function test_response_contains_keys_array(): void
    {
        $response = $this->getJson('/api/v1/.well-known/jwks.json');

        $response->assertStatus(200);
        $response->assertJsonStructure(['keys']);
        $this->assertIsArray($response->json('keys'));
    }

    /**
     * Требование 3.4: массив `keys` содержит хотя бы один ключ.
     */
    public function test_keys_array_is_not_empty(): void
    {
        $response = $this->getJson('/api/v1/.well-known/jwks.json');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('keys'));
    }

    /**
     * Требование 3.4: каждый ключ содержит обязательные поля JWK (RFC 7517 + RFC 7518).
     * Поля: kty, use, alg, kid, n, e.
     */
    public function test_each_key_contains_required_jwk_fields(): void
    {
        $response = $this->getJson('/api/v1/.well-known/jwks.json');

        $response->assertStatus(200);

        $keys = $response->json('keys');
        $this->assertNotEmpty($keys, 'JWKS must contain at least one key');

        foreach ($keys as $index => $key) {
            $this->assertArrayHasKey('kty', $key, "Key #{$index} missing 'kty'");
            $this->assertArrayHasKey('use', $key, "Key #{$index} missing 'use'");
            $this->assertArrayHasKey('alg', $key, "Key #{$index} missing 'alg'");
            $this->assertArrayHasKey('kid', $key, "Key #{$index} missing 'kid'");
            $this->assertArrayHasKey('n', $key, "Key #{$index} missing 'n' (RSA modulus)");
            $this->assertArrayHasKey('e', $key, "Key #{$index} missing 'e' (RSA exponent)");
        }
    }

    /**
     * Требование 3.4: ключ имеет корректные значения полей kty, use, alg.
     */
    public function test_key_has_correct_rsa_rs256_values(): void
    {
        $response = $this->getJson('/api/v1/.well-known/jwks.json');

        $response->assertStatus(200);

        $keys = $response->json('keys');
        $this->assertNotEmpty($keys);

        $firstKey = $keys[0];
        $this->assertSame('RSA', $firstKey['kty'], "kty must be 'RSA'");
        $this->assertSame('sig', $firstKey['use'], "use must be 'sig'");
        $this->assertSame('RS256', $firstKey['alg'], "alg must be 'RS256'");
    }

    /**
     * Требование 3.4: поля n и e содержат непустые base64url-строки.
     */
    public function test_key_n_and_e_are_non_empty_strings(): void
    {
        $response = $this->getJson('/api/v1/.well-known/jwks.json');

        $response->assertStatus(200);

        $keys = $response->json('keys');
        $this->assertNotEmpty($keys);

        $firstKey = $keys[0];
        $this->assertNotEmpty($firstKey['n'], "RSA modulus 'n' must not be empty");
        $this->assertNotEmpty($firstKey['e'], "RSA exponent 'e' must not be empty");
        $this->assertIsString($firstKey['n']);
        $this->assertIsString($firstKey['e']);
    }

    /**
     * Требование 3.4: kid — непустая строка.
     */
    public function test_key_kid_is_non_empty_string(): void
    {
        $response = $this->getJson('/api/v1/.well-known/jwks.json');

        $response->assertStatus(200);

        $keys = $response->json('keys');
        $this->assertNotEmpty($keys);

        $this->assertNotEmpty($keys[0]['kid'], 'kid must not be empty');
        $this->assertIsString($keys[0]['kid']);
    }

    /**
     * Требование 3.7: при наличии предыдущего ключа JWKS содержит два
     * Passport-ключа с разными kid (classroom-ключ считается отдельно).
     */
    public function test_publishes_two_keys_when_previous_key_is_configured(): void
    {
        // Используем текущий публичный ключ как "предыдущий" для теста
        $currentKeyPath = config('passport.public_key_path', storage_path('oauth-public.key'));

        if (! file_exists((string) $currentKeyPath)) {
            $this->markTestSkipped('Public key file not found: '.$currentKeyPath);
        }

        // Временно задаём предыдущий ключ через конфиг
        config(['passport.previous_public_key_path' => $currentKeyPath]);

        $response = $this->getJson('/api/v1/.well-known/jwks.json');

        $response->assertStatus(200);

        // Classroom-ключ публикуется как отдельный ключ (kid начинается с
        // "classroom-"), поэтому ротацию Passport-ключей проверяем по ключам,
        // НЕ относящимся к classroom.
        $passportKeys = array_values(array_filter(
            $response->json('keys'),
            fn (array $key): bool => ! str_starts_with((string) $key['kid'], 'classroom-'),
        ));

        $this->assertCount(2, $passportKeys, 'JWKS must contain 2 Passport keys when previous key is configured');

        // kid должны быть разными (разные суффиксы: current vs previous)
        $this->assertNotSame($passportKeys[0]['kid'], $passportKeys[1]['kid'], 'Keys must have different kid values');
    }

    /**
     * Требование 11.7: JWKS включает публичный ключ для проверки classroom-JWT.
     *
     * Проверяем end-to-end согласование: выпускаем реальный classroom-токен
     * через {@see ClassroomTokenIssuer}, читаем `kid` из его header и убеждаемся,
     * что в JWK Set присутствует ключ с тем же `kid`. Без этого совпадения
     * classroom-сервис не сможет сопоставить ключ и проверить подпись.
     */
    public function test_jwks_contains_classroom_key_matching_issued_token_kid(): void
    {
        $publicKeyPath = config('classroom.jwt_public_key_path');

        if (! $publicKeyPath || ! is_readable((string) $publicKeyPath)) {
            $this->markTestSkipped('Classroom public key not found: '.$publicKeyPath);
        }

        // Выпускаем реальный classroom-токен для тестового урока/пользователя.
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $lesson = Lesson::forceCreate([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => Lesson::PAYMENT_PAID,
            'start_time' => now()->subMinutes(10),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
            'price' => 1000,
            'platform_commission' => 200,
            'net_amount' => 800,
        ]);

        $token = app(ClassroomTokenIssuer::class)->issue($lesson, $tutor);

        // Парсим header токена и извлекаем kid.
        [$encodedHeader] = explode('.', $token);
        $padded = strtr($encodedHeader, '-_', '+/');
        $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
        $header = json_decode((string) base64_decode($padded, true), true);

        $this->assertIsArray($header, 'JWT header must decode to an array');
        $this->assertArrayHasKey('kid', $header, 'Issued classroom token must carry a kid');

        $tokenKid = $header['kid'];
        $this->assertStringStartsWith('classroom-', $tokenKid, 'Classroom token kid must be classroom-prefixed');

        // Ищем ключ с тем же kid в JWK Set.
        $response = $this->getJson('/api/v1/.well-known/jwks.json');
        $response->assertStatus(200);

        $kids = array_column($response->json('keys'), 'kid');
        $this->assertContains(
            $tokenKid,
            $kids,
            'JWKS must publish a classroom key whose kid matches the issued token header',
        );

        // Найденный classroom-ключ должен быть валидным RSA/RS256/sig JWK.
        $classroomKey = collect($response->json('keys'))
            ->firstWhere('kid', $tokenKid);

        $this->assertSame('RSA', $classroomKey['kty']);
        $this->assertSame('sig', $classroomKey['use']);
        $this->assertSame('RS256', $classroomKey['alg']);
        $this->assertNotEmpty($classroomKey['n']);
        $this->assertNotEmpty($classroomKey['e']);
    }

    /**
     * Требование 3.4: Content-Type ответа — application/json.
     */
    public function test_response_content_type_is_json(): void
    {
        $response = $this->getJson('/api/v1/.well-known/jwks.json');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type') ?? '');
    }
}
