<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

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
     * Требование 3.7: при наличии предыдущего ключа JWKS содержит два ключа
     * с разными kid.
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

        $keys = $response->json('keys');
        $this->assertCount(2, $keys, 'JWKS must contain 2 keys when previous key is configured');

        // kid должны быть разными (разные суффиксы: current vs previous)
        $this->assertNotSame($keys[0]['kid'], $keys[1]['kid'], 'Keys must have different kid values');
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
