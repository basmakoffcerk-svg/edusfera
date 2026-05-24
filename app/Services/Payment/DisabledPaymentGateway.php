<?php

declare(strict_types=1);

namespace App\Services\Payment;

use RuntimeException;

class DisabledPaymentGateway implements PaymentGatewayInterface
{
    public function createPayment(array $data): array
    {
        throw new RuntimeException('Payment gateway is disabled for this environment.');
    }

    public function verifyPayment(string $transactionId): bool
    {
        return false;
    }

    public function refundPayment(string $transactionId, float $amount): bool
    {
        throw new RuntimeException('Payment gateway is disabled for this environment.');
    }

    public function getCallbackUrl(): string
    {
        throw new RuntimeException('Payment gateway is disabled for this environment.');
    }
}
