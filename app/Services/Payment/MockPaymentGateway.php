<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGatewayInterface
{
    public function createPayment(array $data): array
    {
        $payload = [
            'success' => true,
            'gateway_transaction_id' => 'mock_' . Str::uuid(),
            'status' => 'success',
            'callback_url' => $this->getCallbackUrl(),
            'payload' => $data,
        ];

        Log::channel('payments')->info('Mock payment created', $payload);

        return $payload;
    }

    public function verifyPayment(string $transactionId): bool
    {
        Log::channel('payments')->info('Mock payment verified', [
            'gateway_transaction_id' => $transactionId,
        ]);

        return true;
    }

    public function refundPayment(string $transactionId, float $amount): bool
    {
        Log::channel('payments')->info('Mock payment refunded', [
            'gateway_transaction_id' => $transactionId,
            'amount' => number_format($amount, 2, '.', ''),
        ]);

        return true;
    }

    public function getCallbackUrl(): string
    {
        return route('payments.webhook');
    }
}
