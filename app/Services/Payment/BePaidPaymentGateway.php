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
    private string $checkoutUrl;
    private string $gatewayUrl;
    private int $timeoutSeconds;

    public function __construct()
    {
        $this->shopId = (string) config('payments.bepaid.shop_id', '');
        $this->secretKey = (string) config('payments.bepaid.secret_key', '');
        $this->testMode = (bool) config('payments.bepaid.test_mode', true);
        $this->checkoutUrl = (string) config('payments.bepaid.checkout_url', 'https://checkout.bepaid.by/ctp/api/checkouts');
        $this->gatewayUrl = (string) config('payments.bepaid.gateway_url', 'https://gateway.bepaid.by');
        $this->timeoutSeconds = (int) config('payments.bepaid.timeout_seconds', 30);
    }

    public function createPayment(array $data): array
    {
        // bePaid expects amount in cents/kopecks
        $amountInCents = (int) round((float) $data['amount'] * 100);

        $isWalletTopUp = isset($data['wallet_topup']) && $data['wallet_topup'] === true;

        if ($isWalletTopUp) {
            $successUrl = route('filament.admin.pages.wallet');
            $cancelUrl = route('filament.admin.pages.wallet');
            $description = 'Пополнение кошелька на Edusfera (Пользователь #' . $data['user_id'] . ')';
            $trackingId = 'wallet_topup_user_' . $data['user_id'] . '_' . time();
        } else {
            $lessonId = $data['lesson_id'];
            $successUrl = route('checkout.success', ['lesson' => $lessonId]);
            $cancelUrl = route('checkout.show', ['lesson' => $lessonId]);
            $description = 'Оплата занятия на Edusfera (Урок #' . $lessonId . ')';
            $trackingId = 'lesson_' . $lessonId . '_user_' . $data['user_id'] . '_' . time();
        }

        $payload = [
            'checkout' => [
                'version' => 2.1,
                'test' => $this->testMode,
                'transaction_type' => 'payment',
                'attempts' => 3,
                'settings' => [
                    'success_url' => $successUrl,
                    'decline_url' => $cancelUrl,
                    'fail_url' => $cancelUrl,
                    'cancel_url' => $cancelUrl,
                    'notification_url' => $this->getCallbackUrl(),
                    'language' => 'ru',
                ],
                'order' => [
                    'amount' => $amountInCents,
                    'currency' => $data['currency'],
                    'description' => $description,
                    'tracking_id' => $trackingId,
                ],
            ]
        ];

        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->timeout($this->timeoutSeconds)
                ->retry(2, 200)
                ->post($this->checkoutUrl, $payload);

            if ($response->successful() && isset($response['checkout']['redirect_url'])) {
                $token = $response['checkout']['token'] ?? '';
                Log::channel('payments')->info('bePaid checkout created', [
                    'token' => $token,
                    'lesson_id' => $isWalletTopUp ? null : $data['lesson_id'],
                    'wallet_topup' => $isWalletTopUp,
                ]);

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
        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->timeout($this->timeoutSeconds)
                ->retry(2, 200)
                ->get($this->checkoutUrl . '/' . $transactionId);

            if ($response->successful() && isset($response['checkout']['status'])) {
                $status = $response['checkout']['status'];
                Log::channel('payments')->info('bePaid verify check', ['transaction_id' => $transactionId, 'status' => $status]);

                return $status === 'successful';
            }

            Log::channel('payments')->error('bePaid verify failed', ['transaction_id' => $transactionId, 'response' => $response->body()]);
        } catch (\Exception $e) {
            Log::channel('payments')->error('bePaid verify exception', ['transaction_id' => $transactionId, 'message' => $e->getMessage()]);
        }

        return false;
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
                ->timeout($this->timeoutSeconds)
                ->retry(2, 200)
                ->post($this->gatewayUrl . '/transactions/refunds', $payload);

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
