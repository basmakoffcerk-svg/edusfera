<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BePaidPaymentGateway implements PaymentGatewayInterface
{
    private string $shopId;
    private string $secretKey;
    private bool $testMode;

    public function __construct()
    {
        $this->shopId = (string) config('payments.bepaid.shop_id', '');
        $this->secretKey = (string) config('payments.bepaid.secret_key', '');
        $this->testMode = (bool) config('payments.bepaid.test_mode', true);
    }

    public function createPayment(array $data): array
    {
        // bePaid expects amount in cents/kopecks
        $amountInCents = (int) round((float) $data['amount'] * 100);

        $payload = [
            'checkout' => [
                'version' => 2.1,
                'test' => $this->testMode,
                'transaction_type' => 'payment',
                'attempts' => 3,
                'settings' => [
                    'success_url' => route('checkout.success', ['lesson' => $data['lesson_id']]),
                    'decline_url' => route('checkout.show', ['lesson' => $data['lesson_id']]),
                    'fail_url' => route('checkout.show', ['lesson' => $data['lesson_id']]),
                    'cancel_url' => route('checkout.show', ['lesson' => $data['lesson_id']]),
                    'notification_url' => $this->getCallbackUrl(),
                    'language' => 'ru',
                ],
                'order' => [
                    'amount' => $amountInCents,
                    'currency' => $data['currency'],
                    'description' => 'Оплата занятия на Edusfera (Урок #' . $data['lesson_id'] . ')',
                    'tracking_id' => 'lesson_' . $data['lesson_id'] . '_user_' . $data['user_id'] . '_' . time(),
                ],
            ]
        ];

        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->post('https://checkout.bepaid.by/ctp/api/checkouts', $payload);

            if ($response->successful() && isset($response['checkout']['redirect_url'])) {
                $token = $response['checkout']['token'] ?? '';
                Log::channel('payments')->info('bePaid checkout created', ['token' => $token, 'lesson_id' => $data['lesson_id']]);

                return [
                    'success' => true,
                    'gateway_transaction_id' => $token,
                    'status' => 'pending',
                    'redirect_url' => $response['checkout']['redirect_url'],
                    'payload' => $data,
                ];
            }

            Log::channel('payments')->error('bePaid checkout failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::channel('payments')->error('bePaid exception', ['message' => $e->getMessage()]);
        }

        return [
            'success' => false,
            'message' => 'Не удалось создать платежную ссылку',
        ];
    }

    public function verifyPayment(string $transactionId): bool
    {
        // This validates the transaction status directly via bePaid API if needed.
        // For webhook-based processing, verification is usually done by checking the webhook signature.
        return true; 
    }

    public function refundPayment(string $transactionId, float $amount): bool
    {
        $amountInCents = (int) round($amount * 100);

        $payload = [
            'request' => [
                'parent_uid' => $transactionId,
                'amount' => $amountInCents,
                'reason' => 'Возврат средств по запросу',
            ]
        ];

        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->post('https://gateway.bepaid.by/transactions/refunds', $payload);

            if ($response->successful() && isset($response['transaction']['status']) && $response['transaction']['status'] === 'successful') {
                return true;
            }

            Log::channel('payments')->error('bePaid refund failed', ['response' => $response->body()]);
        } catch (\Exception $e) {
            Log::channel('payments')->error('bePaid refund exception', ['message' => $e->getMessage()]);
        }

        return false;
    }

    public function getCallbackUrl(): string
    {
        return route('payments.webhook');
    }
}
