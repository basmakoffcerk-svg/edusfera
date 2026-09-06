<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LedgerClient
{
    private string $baseUrl;
    private bool $enabled;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ledger.url', env('LEDGER_URL', 'http://ledger:8080')), '/');
        $this->enabled = (bool) config('services.ledger.enabled', env('LEDGER_ENABLED', true));
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Создать кошелек в Ledger.
     */
    public function createWallet(string|int $userId, string $currency = 'BYN'): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $response = Http::withToken($this->generateJwtToken())
                ->timeout(3)
                ->post("{$this->baseUrl}/wallets", [
                    'user_id' => (string) $userId,
                    'currency' => $currency,
                ]);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::error('Ошибка создания кошелька в Ledger', [
                'user_id' => $userId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Ledger микросервис недоступен при создании кошелька', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Получить кошелек по user_id.
     */
    public function getWalletByUserId(string|int $userId): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $response = Http::withToken($this->generateJwtToken())
                ->timeout(3)
                ->get("{$this->baseUrl}/wallets/by-user/{$userId}");

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() !== 404) {
                Log::error('Ошибка поиска кошелька по user_id в Ledger', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Ledger микросервис недоступен при поиске кошелька', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Получить балансы кошелька.
     */
    public function getBalance(string $walletId): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $response = Http::withToken($this->generateJwtToken())
                ->timeout(3)
                ->get("{$this->baseUrl}/wallets/{$walletId}/balance");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Ошибка получения баланса из Ledger', [
                'wallet_id' => $walletId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Ledger микросервис недоступен при запросе баланса', [
                'wallet_id' => $walletId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Создать транзакцию в Ledger (депозит, списание, холд).
     */
    public function createTransaction(
        string $walletId,
        string $amount,
        string $type,
        string $status,
        ?string $externalId = null
    ): ?array {
        if (!$this->enabled) {
            return null;
        }

        try {
            $response = Http::withToken($this->generateJwtToken())
                ->timeout(3)
                ->post("{$this->baseUrl}/transactions", [
                    'wallet_id' => $walletId,
                    'amount' => $amount,
                    'type' => $type,
                    'status' => $status,
                    'external_id' => $externalId,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Ошибка создания транзакции в Ledger', [
                'wallet_id' => $walletId,
                'amount' => $amount,
                'type' => $type,
                'status' => $status,
                'response' => $response->json(),
            ]);

            if ($response->json('error') === 'insufficient_funds') {
                throw new \RuntimeException('insufficient_funds');
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Ledger микросервис недоступен при создании транзакции', [
                'wallet_id' => $walletId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Создать групповой перевод (Double-Entry).
     */
    public function createTransfer(array $postings, ?string $externalId = null): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $response = Http::withToken($this->generateJwtToken())
                ->timeout(3)
                ->post("{$this->baseUrl}/transfers", [
                    'external_id' => $externalId,
                    'postings' => $postings,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Ошибка создания перевода (Double-Entry) в Ledger', [
                'external_id' => $externalId,
                'postings' => $postings,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $errCode = $response->json('error');
            if ($errCode && str_starts_with($errCode, 'insufficient_funds')) {
                throw new \RuntimeException('insufficient_funds');
            }
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Ledger микросервис недоступен при переводе', [
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Обновить статус транзакции по ID.
     */
    public function updateTransactionStatus(string $transactionId, string $status): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $response = Http::withToken($this->generateJwtToken())
                ->timeout(3)
                ->post("{$this->baseUrl}/transactions/{$transactionId}/status", [
                    'status' => $status,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Ошибка обновления статуса транзакции в Ledger', [
                'transaction_id' => $transactionId,
                'status' => $status,
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Ledger микросервис недоступен при обновлении статуса транзакции', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Массово обновить статус транзакций по external_id.
     */
    public function updateStatusByExternalId(string $externalId, string $status): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $response = Http::withToken($this->generateJwtToken())
                ->timeout(3)
                ->post("{$this->baseUrl}/transactions/external/{$externalId}/status", [
                    'status' => $status,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Ошибка обновления статуса транзакций по external_id в Ledger', [
                'external_id' => $externalId,
                'status' => $status,
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Ledger микросервис недоступен при обновлении транзакций по external_id', [
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Генерирует JWT токен, подписанный приватным ключом Passport (RS256).
     */
    private function generateJwtToken(): string
    {
        $privateKeyPath = storage_path('oauth-private.key');
        if (!file_exists($privateKeyPath)) {
            // Если в тестах/локально файл ключа отсутствует, используем заглушку
            // для бесшовного прохождения тестов без пре-генерации ключей.
            Log::warning('Приватный ключ Passport не найден. Использование временного JWT токена.');
            return 'fake-token-unsigned';
        }

        $privateKey = file_get_contents($privateKeyPath);

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'iss' => 'edusfera-core',
            'iat' => time(),
            'exp' => time() + 300, // Срок жизни 5 минут
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode($payload);

        $signatureInput = $base64UrlHeader . '.' . $base64UrlPayload;

        $signature = '';
        if (!openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Не удалось подписать JWT через OpenSSL');
        }

        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
