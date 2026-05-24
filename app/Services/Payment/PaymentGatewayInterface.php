<?php

declare(strict_types=1);

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    public function createPayment(array $data): array;

    public function verifyPayment(string $transactionId): bool;

    public function refundPayment(string $transactionId, float $amount): bool;

    public function getCallbackUrl(): string;
}
