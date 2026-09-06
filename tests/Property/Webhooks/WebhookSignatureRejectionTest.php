<?php

declare(strict_types=1);

namespace Tests\Property\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Property-based тест для correctness property P4 (design.md, раздел 6) на примере
 * приёмника AiWebhookController (`POST /webhooks/ai/recommendations`), который
 * наследует App\Http\Webhooks\AbstractWebhookController.
 *
 * Feature: microservices-foundation, Property P4: invalid webhook signature rejected without side effects
 *
 * Свойство формулируется так (квантор по всем входящим запросам):
 *
 *   ∀ request R с невалидной подписью / протухшим timestamp / повторным nonce:
 *       response(R).status ∈ {401, 409}
 *       state(DB) after R = state(DB) before R   (никаких побочных эффектов)
 *
 * Проверяется тремя сценариями (суммарно ≥100 итераций, каждый ≥30), все
 * генераторы детерминированы через mt_srand с фиксированным сидом:
 *
 *   Сценарий 1 (≥40) — заведомо неверная подпись → 401 webhook_invalid_signature.
 *   Сценарий 2 (≥30) — валидная подпись, но X-Timestamp вне окна ±300s
 *                      → 401 webhook_timestamp_invalid.
 *   Сценарий 3 (≥30) — валидная подпись + timestamp, но повтор nonce → 409
 *                      webhook_nonce_replayed (первый запрос 2xx).
 *
 * Во всех сценариях, где запрос отвергается, утверждается отсутствие побочных
 * эффектов: количество строк в integration_outbox и transactions до запроса
 * равно количеству после (требования 10.3, 10.6).
 *
 * Хранилище nonce — Cache::store(config('cache.default')); в тестах это
 * array-драйвер (CACHE_STORE=array из phpunit.xml), без реального Redis.
 *
 * **Validates: Requirements 10.2, 10.3, 10.4, 10.5**
 */
class WebhookSignatureRejectionTest extends TestCase
{
    use RefreshDatabase;

    private const URI = '/webhooks/ai/recommendations';

    private const SECRET = 'ai-test-secret-1234567890';

    /** Сценарий 1: число итераций с заведомо неверной подписью (≥40). */
    private const ITERATIONS_INVALID_SIGNATURE = 40;

    /** Сценарий 2: число итераций с протухшим timestamp (≥30). */
    private const ITERATIONS_STALE_TIMESTAMP = 35;

    /** Сценарий 3: число итераций с повтором nonce (≥30). */
    private const ITERATIONS_REPLAYED_NONCE = 35;

    /** Допустимый дрейф timestamp в секундах (требование 10.4). */
    private const MAX_DRIFT_SECONDS = 300;

    protected function setUp(): void
    {
        parent::setUp();

        // Секрет AI-вебхука, которым подписываются запросы (требование 10.2).
        config(['services.ai.webhook_secret' => self::SECRET]);

        // Детерминированное и изолированное хранилище nonce между прогонами.
        Cache::store(config('cache.default'))->flush();
    }

    /**
     * Сценарий 1: заведомо неверная подпись отвергается с 401 без побочных
     * эффектов (требования 10.2, 10.3).
     *
     * Для каждого случайного тела генерируется случайный hex, заведомо не
     * совпадающий с корректным HMAC-SHA256(body, secret). Подпись проверяется
     * первой в pipeline, поэтому timestamp и nonce могут быть произвольными.
     */
    public function test_invalid_signature_rejected_without_side_effects(): void
    {
        mt_srand(40401);

        for ($i = 0; $i < self::ITERATIONS_INVALID_SIGNATURE; $i++) {
            $body = $this->randomJsonBody();
            $invalidSignature = $this->randomInvalidSignature($body);

            // Произвольные (потенциально валидные) timestamp/nonce — не должны
            // влиять на результат, т.к. подпись проверяется первой.
            $timestamp = (string) (Carbon::now('UTC')->getTimestamp() + mt_rand(-250, 250));
            $nonce = $this->uniqueNonce('sig', $i);

            $context = sprintf(
                'scenario=invalid_signature i=%d body=%s sig=%s ts=%s nonce=%s',
                $i,
                $body,
                $invalidSignature,
                $timestamp,
                $nonce,
            );

            $outboxBefore = $this->outboxCount();
            $transactionsBefore = $this->transactionsCount();

            $response = $this->postWebhook($body, [
                'X-Signature' => $invalidSignature,
                'X-Timestamp' => $timestamp,
                'X-Nonce' => $nonce,
            ]);

            $response->assertStatus(401, $context);
            $response->assertJsonPath('error.code', 'webhook_invalid_signature');

            // Никаких побочных эффектов: pipeline прерван до доменной логики.
            $this->assertSame($outboxBefore, $this->outboxCount(), 'integration_outbox изменился: '.$context);
            $this->assertSame($transactionsBefore, $this->transactionsCount(), 'transactions изменился: '.$context);
        }
    }

    /**
     * Сценарий 2: валидная подпись, но X-Timestamp вне окна ±300s → 401
     * webhook_timestamp_invalid без побочных эффектов (требование 10.4).
     */
    public function test_stale_timestamp_rejected_without_side_effects(): void
    {
        mt_srand(40402);

        for ($i = 0; $i < self::ITERATIONS_STALE_TIMESTAMP; $i++) {
            $body = $this->randomJsonBody();

            // Смещение строго больше окна: 301..100000с в прошлое или будущее.
            $offset = mt_rand(self::MAX_DRIFT_SECONDS + 1, 100000);
            $direction = mt_rand(0, 1) === 0 ? -1 : 1;
            $timestamp = (string) (Carbon::now('UTC')->getTimestamp() + ($direction * $offset));

            $nonce = $this->uniqueNonce('ts', $i);

            $context = sprintf(
                'scenario=stale_timestamp i=%d body=%s offset=%d dir=%d ts=%s nonce=%s',
                $i,
                $body,
                $offset,
                $direction,
                $timestamp,
                $nonce,
            );

            $outboxBefore = $this->outboxCount();
            $transactionsBefore = $this->transactionsCount();

            $response = $this->postWebhook($body, [
                'X-Signature' => $this->sign($body),
                'X-Timestamp' => $timestamp,
                'X-Nonce' => $nonce,
            ]);

            $response->assertStatus(401, $context);
            $response->assertJsonPath('error.code', 'webhook_timestamp_invalid');

            $this->assertSame($outboxBefore, $this->outboxCount(), 'integration_outbox изменился: '.$context);
            $this->assertSame($transactionsBefore, $this->transactionsCount(), 'transactions изменился: '.$context);
        }
    }

    /**
     * Сценарий 3: валидная подпись + timestamp в окне, повтор nonce → 409
     * webhook_nonce_replayed без побочных эффектов (требование 10.5).
     *
     * Первый запрос с уникальным nonce проходит (2xx), повтор того же nonce
     * отвергается. Снимок счётчиков делается ДО повтора и сравнивается ПОСЛЕ.
     */
    public function test_replayed_nonce_rejected_without_side_effects(): void
    {
        mt_srand(40403);

        for ($i = 0; $i < self::ITERATIONS_REPLAYED_NONCE; $i++) {
            $body = $this->randomJsonBody();
            $signature = $this->sign($body);
            $nonce = $this->uniqueNonce('nonce', $i);
            $timestamp = (string) (Carbon::now('UTC')->getTimestamp() + mt_rand(-200, 200));

            $context = sprintf(
                'scenario=replayed_nonce i=%d body=%s ts=%s nonce=%s',
                $i,
                $body,
                $timestamp,
                $nonce,
            );

            // Первый запрос с уникальным nonce должен пройти проверки и вернуть 2xx.
            $first = $this->postWebhook($body, [
                'X-Signature' => $signature,
                'X-Timestamp' => $timestamp,
                'X-Nonce' => $nonce,
            ]);

            $first->assertSuccessful();
            $first->assertJsonPath('status', 'accepted');

            // Снимок состояния перед повтором того же nonce.
            $outboxBefore = $this->outboxCount();
            $transactionsBefore = $this->transactionsCount();

            $replay = $this->postWebhook($body, [
                'X-Signature' => $signature,
                'X-Timestamp' => $timestamp,
                'X-Nonce' => $nonce,
            ]);

            $replay->assertStatus(409, $context);
            $replay->assertJsonPath('error.code', 'webhook_nonce_replayed');

            $this->assertSame($outboxBefore, $this->outboxCount(), 'integration_outbox изменился: '.$context);
            $this->assertSame($transactionsBefore, $this->transactionsCount(), 'transactions изменился: '.$context);
        }
    }

    /**
     * Отправляет POST с заданным сырым телом и заголовками, контролируя точное
     * содержимое тела (важно для корректного HMAC над raw_body).
     *
     * @param  array<string, string>  $headers
     */
    private function postWebhook(string $body, array $headers): \Illuminate\Testing\TestResponse
    {
        $server = $this->transformHeadersToServerVars(array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $headers));

        return $this->call('POST', self::URI, [], [], [], $server, $body);
    }

    /**
     * Вычисляет корректную HMAC-SHA256 подпись в формате `sha256=<hex>`.
     */
    private function sign(string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $body, self::SECRET);
    }

    /**
     * Генерирует подпись `sha256=<hex>`, заведомо НЕ совпадающую с корректным HMAC.
     */
    private function randomInvalidSignature(string $body): string
    {
        $correct = hash_hmac('sha256', $body, self::SECRET);

        do {
            $candidate = $this->randomHex(64);
        } while (hash_equals($correct, $candidate));

        return 'sha256='.$candidate;
    }

    /**
     * Случайное JSON-тело с произвольным набором ключей и скалярных значений.
     */
    private function randomJsonBody(): string
    {
        $keys = ['recommendation', 'lesson_id', 'student_id', 'score', 'note', 'topic', 'payload'];
        $fields = mt_rand(1, 5);
        $body = [];

        for ($i = 0; $i < $fields; $i++) {
            $key = $keys[mt_rand(0, count($keys) - 1)].'_'.mt_rand(0, 99999);
            $body[$key] = $this->randomScalar();
        }

        return json_encode($body, JSON_THROW_ON_ERROR);
    }

    /**
     * Случайное скалярное значение (int / float / bool / string).
     */
    private function randomScalar(): int|float|bool|string
    {
        return match (mt_rand(0, 3)) {
            0 => mt_rand(-100000, 100000),
            1 => mt_rand(0, 100000) / 100,
            2 => mt_rand(0, 1) === 1,
            default => $this->randomHex(mt_rand(1, 24)),
        };
    }

    /**
     * Случайная hex-строка заданной длины (детерминирована через mt_rand).
     */
    private function randomHex(int $length): string
    {
        $hex = '';
        for ($i = 0; $i < $length; $i++) {
            $hex .= dechex(mt_rand(0, 15));
        }

        return $hex;
    }

    /**
     * Уникальный nonce для итерации (гарантирует отсутствие коллизий в дедупе).
     */
    private function uniqueNonce(string $scenario, int $iteration): string
    {
        return sprintf('nonce-%s-%d-%d', $scenario, $iteration, mt_rand(0, PHP_INT_MAX));
    }

    /**
     * Текущее число строк в integration_outbox (детектор побочных эффектов).
     */
    private function outboxCount(): int
    {
        return DB::table('integration_outbox')->count();
    }

    /**
     * Текущее число строк в transactions (детектор побочных эффектов).
     */
    private function transactionsCount(): int
    {
        return DB::table('transactions')->count();
    }
}
