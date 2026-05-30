<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Покрывает требование 10 спеки microservices-foundation на примере приёмника
 * AiWebhookController (`POST /webhooks/ai/recommendations`), который наследует
 * App\Http\Webhooks\AbstractWebhookController:
 *
 *   10.2/10.3 — `X-Signature: sha256=<hmac>`; невалидная/отсутствующая → 401.
 *   10.4      — `X-Timestamp` за пределами ±300s → 401.
 *   10.5      — `X-Nonce` повторно → 409; nonce хранится в Cache
 *               (`webhook:nonce:{source}:{nonce}`, TTL 600s).
 *   10.6      — при успешных проверках делегирует handler'у и возвращает 200.
 *
 * Хранилище nonce — Cache::store(config('cache.default')); в тестах это
 * array-драйвер (CACHE_STORE=array из phpunit.xml), без реального Redis.
 */
class WebhookReceiverTest extends TestCase
{
    private const URI = '/webhooks/ai/recommendations';

    private const SECRET = 'ai-test-secret-1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        // Секрет AI-вебхука, которым подписываются запросы (требование 10.2).
        config(['services.ai.webhook_secret' => self::SECRET]);
    }

    public function test_valid_signature_timestamp_and_fresh_nonce_returns_200(): void
    {
        $body = json_encode(['recommendation' => 'do homework #1']);

        $response = $this->postWebhook($body, [
            'X-Signature' => $this->sign($body),
            'X-Timestamp' => (string) Carbon::now('UTC')->getTimestamp(),
            'X-Nonce' => 'nonce-valid-001',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'accepted');

        // Nonce сохранён в Cache под ожидаемым ключом (требование 10.5).
        $this->assertTrue(
            Cache::store(config('cache.default'))->has('webhook:nonce:ai:nonce-valid-001')
        );
    }

    public function test_invalid_signature_returns_401_without_side_effects(): void
    {
        $body = json_encode(['recommendation' => 'tampered']);

        $response = $this->postWebhook($body, [
            'X-Signature' => 'sha256='.str_repeat('0', 64),
            'X-Timestamp' => (string) Carbon::now('UTC')->getTimestamp(),
            'X-Nonce' => 'nonce-bad-sig-001',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'webhook_invalid_signature');

        // Никаких побочных эффектов: nonce не сохранён, т.к. проверка подписи
        // выполняется первой и прерывает pipeline (требование 10.3).
        $this->assertFalse(
            Cache::store(config('cache.default'))->has('webhook:nonce:ai:nonce-bad-sig-001')
        );
    }

    public function test_timestamp_outside_window_returns_401(): void
    {
        $body = json_encode(['recommendation' => 'stale']);

        // Метка времени на 10 минут в прошлом — за пределами окна ±5 минут.
        $staleTimestamp = Carbon::now('UTC')->subSeconds(600)->getTimestamp();

        $response = $this->postWebhook($body, [
            'X-Signature' => $this->sign($body),
            'X-Timestamp' => (string) $staleTimestamp,
            'X-Nonce' => 'nonce-stale-001',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'webhook_timestamp_invalid');
    }

    public function test_replayed_nonce_returns_409(): void
    {
        $body = json_encode(['recommendation' => 'replay']);
        $nonce = 'nonce-replay-001';

        // Первый запрос проходит успешно и сохраняет nonce.
        $this->postWebhook($body, [
            'X-Signature' => $this->sign($body),
            'X-Timestamp' => (string) Carbon::now('UTC')->getTimestamp(),
            'X-Nonce' => $nonce,
        ])->assertOk();

        // Повтор того же nonce → 409 Conflict (требование 10.5).
        $response = $this->postWebhook($body, [
            'X-Signature' => $this->sign($body),
            'X-Timestamp' => (string) Carbon::now('UTC')->getTimestamp(),
            'X-Nonce' => $nonce,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('error.code', 'webhook_nonce_replayed');
    }

    public function test_missing_signature_returns_401(): void
    {
        $body = json_encode(['recommendation' => 'no signature']);

        $response = $this->postWebhook($body, [
            'X-Timestamp' => (string) Carbon::now('UTC')->getTimestamp(),
            'X-Nonce' => 'nonce-missing-sig-001',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'webhook_invalid_signature');
    }

    /**
     * Вычисляет HMAC-SHA256 подпись в формате `sha256=<hex>` над сырым телом.
     */
    private function sign(string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $body, self::SECRET);
    }

    /**
     * Отправляет POST с заданным сырым телом и заголовками, контролируя точное
     * содержимое тела (важно для корректного HMAC над raw_body).
     *
     * @param  array<string, string>  $headers
     */
    private function postWebhook(string $body, array $headers)
    {
        $server = $this->transformHeadersToServerVars(array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $headers));

        return $this->call('POST', self::URI, [], [], [], $server, $body);
    }
}
