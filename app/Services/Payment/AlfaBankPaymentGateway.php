<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlfaBankPaymentGateway implements PaymentGatewayInterface
{
    private string $userName;

    private string $password;

    private string $token;

    private bool $testMode;

    private string $apiUrl;

    private string $currencyCode;

    private int $holdPeriodDays;

    public function __construct()
    {
        $this->userName = (string) config('payments.alfabank.user_name', '');
        $this->password = (string) config('payments.alfabank.password', '');
        $this->token = (string) config('payments.alfabank.token', '');
        $this->testMode = (bool) config('payments.alfabank.test_mode', true);
        $this->apiUrl = rtrim((string) config('payments.alfabank.api_url', 'https://web.rbsuat.com/ab_by/rest'), '/');
        $this->currencyCode = (string) config('payments.alfabank.currency_code', '933'); // 933 = BYN
        $this->holdPeriodDays = (int) config('payments.alfabank.hold_period_days', 7);
    }

    /**
     * Создание платежа / преавторизация в Альфа-Банке:
     * Для уроков и Escrow используется registerPreAuth.do (двухстадийная оплата с холдированием).
     * Для мгновенных пополнений баланса/подписок может использоваться register.do или registerPreAuth.do.
     */
    public function createPayment(array $data): array
    {
        $amount = (float) ($data['amount'] ?? 0);
        // Сумма в копейках (минимальная единица валюты)
        $amountInKopecks = (int) round($amount * 100);
        $isWalletTopUp = isset($data['wallet_topup']) && $data['wallet_topup'] === true;
        $isSubscription = isset($data['subscription']) && $data['subscription'] === true;

        if ($isWalletTopUp) {
            $returnUrl = route('filament.admin.pages.wallet');
            $failUrl = route('filament.admin.pages.wallet');
            $description = 'Пополнение баланса Edusfera (Пользователь #'.($data['user_id'] ?? 0).')';
            $orderNumber = 'wallet_'.($data['user_id'] ?? 0).'_'.time();
        } elseif ($isSubscription) {
            $returnUrl = route('filament.admin.pages.tutor-subscription-page');
            $failUrl = route('filament.admin.pages.tutor-subscription-page');
            $description = 'Оплата подписки Edusfera (Преподаватель #'.($data['user_id'] ?? 0).')';
            $orderNumber = 'sub_'.($data['user_id'] ?? 0).'_'.time();
        } else {
            $lessonId = $data['lesson_id'] ?? 0;
            $returnUrl = route('checkout.success', ['lesson' => $lessonId]);
            $failUrl = route('checkout.show', ['lesson' => $lessonId]);
            $description = 'Оплата занятия Edusfera (Урок #'.$lessonId.')';
            $orderNumber = 'lesson_'.$lessonId.'_'.time();
        }

        // Двухстадийная оплата (registerPreAuth.do) для безопасного холдирования
        $endpoint = $this->apiUrl.'/registerPreAuth.do';

        $params = [
            'orderNumber' => $orderNumber,
            'amount' => $amountInKopecks,
            'currency' => $this->currencyCode,
            'returnUrl' => $returnUrl,
            'failUrl' => $failUrl,
            'description' => mb_substr($description, 0, 100),
            'language' => 'ru',
        ];

        if (! empty($this->token)) {
            $params['token'] = $this->token;
        } else {
            $params['userName'] = $this->userName;
            $params['password'] = $this->password;
        }

        try {
            if (! empty($this->userName) && ! empty($this->password)) {
                $response = Http::asForm()
                    ->timeout(30)
                    ->post($endpoint, $params);

                if ($response->successful()) {
                    $json = $response->json();
                    $errorCode = (int) ($json['errorCode'] ?? 0);

                    if ($errorCode === 0 && ! empty($json['orderId'])) {
                        $orderId = (string) $json['orderId'];
                        $formUrl = (string) ($json['formUrl'] ?? '');

                        Log::channel('payments')->info('Alfa-Bank payment registered', [
                            'orderNumber' => $orderNumber,
                            'orderId' => $orderId,
                        ]);

                        return [
                            'success' => true,
                            'gateway_transaction_id' => $orderId,
                            'mdOrder' => $orderId,
                            'status' => 'authorized',
                            'redirect_url' => $formUrl,
                            'web_sdk_url' => $this->getWebSdkUrl(),
                            'api_context' => $this->getApiContext(),
                            'payload' => array_merge($params, ['orderId' => $orderId]),
                        ];
                    }

                    Log::channel('payments')->error('Alfa-Bank registerPreAuth API error', [
                        'errorCode' => $errorCode,
                        'errorMessage' => $json['errorMessage'] ?? '',
                        'orderNumber' => $orderNumber,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::channel('payments')->error('Alfa-Bank create payment exception', [
                'message' => $e->getMessage(),
                'orderNumber' => $orderNumber,
            ]);
        }

        // Sandbox / Test fallback
        if ($this->testMode) {
            $mockOrderId = 'alfa_sb_'.bin2hex(random_bytes(8));
            Log::channel('payments')->info('Alfa-Bank sandbox test payment authorized', [
                'orderNumber' => $orderNumber,
                'mockOrderId' => $mockOrderId,
            ]);

            return [
                'success' => true,
                'gateway_transaction_id' => $mockOrderId,
                'mdOrder' => $mockOrderId,
                'status' => 'authorized',
                'redirect_url' => $returnUrl,
                'web_sdk_url' => $this->getWebSdkUrl(),
                'api_context' => $this->getApiContext(),
                'payload' => array_merge($params, ['orderId' => $mockOrderId]),
            ];
        }

        return [
            'success' => false,
            'message' => 'Не удалось авторизовать платёж через ЗАО «Альфа-Банк»',
        ];
    }

    public function getWebSdkUrl(): string
    {
        return (string) config(
            'payments.alfabank.web_sdk_url',
            $this->testMode
                ? 'https://abby.rbsuat.com/payment/modules/multiframe/main.js'
                : 'https://ecom.alfabank.by/payment/modules/multiframe/main.js'
        );
    }

    public function getApiContext(): string
    {
        return (string) config('payments.alfabank.api_context', '/payment');
    }

    /**
     * Проверка статуса заказа в Альфа-Банке:
     * getOrderStatusExtended.do
     */
    public function verifyPayment(string $transactionId): bool
    {
        if ($this->testMode && (str_starts_with($transactionId, 'alfa_sb_') || empty($this->userName))) {
            return true;
        }

        $endpoint = $this->apiUrl.'/getOrderStatusExtended.do';
        $params = [
            'orderId' => $transactionId,
        ];

        if (! empty($this->token)) {
            $params['token'] = $this->token;
        } else {
            $params['userName'] = $this->userName;
            $params['password'] = $this->password;
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($endpoint, $params);

            if ($response->successful()) {
                $json = $response->json();
                $orderStatus = (int) ($json['orderStatus'] ?? -1);
                // 1 = сумма захолдирована (pre-authorized), 2 = сумма списана (deposited)
                return in_array($orderStatus, [1, 2], true);
            }
        } catch (\Exception $e) {
            Log::channel('payments')->error('Alfa-Bank verify payment exception', [
                'transactionId' => $transactionId,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Подтверждение списания захолдированной суммы (Escrow capture):
     * deposit.do
     */
    public function capturePayment(string $transactionId, float $amount): bool
    {
        if ($this->testMode && str_starts_with($transactionId, 'alfa_sb_')) {
            Log::channel('payments')->info('Alfa-Bank test deposit.do mock capture success', [
                'transactionId' => $transactionId,
                'amount' => $amount,
            ]);

            return true;
        }

        $endpoint = $this->apiUrl.'/deposit.do';
        $amountInKopecks = (int) round($amount * 100);

        $params = [
            'orderId' => $transactionId,
            'amount' => $amountInKopecks,
        ];

        if (! empty($this->token)) {
            $params['token'] = $this->token;
        } else {
            $params['userName'] = $this->userName;
            $params['password'] = $this->password;
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post($endpoint, $params);

            if ($response->successful()) {
                $json = $response->json();
                $errorCode = (int) ($json['errorCode'] ?? -1);

                return $errorCode === 0;
            }
        } catch (\Exception $e) {
            Log::channel('payments')->error('Alfa-Bank deposit exception', [
                'transactionId' => $transactionId,
                'amount' => $amount,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Отмена преавторизации / холдирования:
     * reverse.do
     */
    public function voidPayment(string $transactionId): bool
    {
        if ($this->testMode && str_starts_with($transactionId, 'alfa_sb_')) {
            Log::channel('payments')->info('Alfa-Bank test reverse.do mock void success', [
                'transactionId' => $transactionId,
            ]);

            return true;
        }

        $endpoint = $this->apiUrl.'/reverse.do';
        $params = [
            'orderId' => $transactionId,
        ];

        if (! empty($this->token)) {
            $params['token'] = $this->token;
        } else {
            $params['userName'] = $this->userName;
            $params['password'] = $this->password;
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post($endpoint, $params);

            if ($response->successful()) {
                $json = $response->json();
                $errorCode = (int) ($json['errorCode'] ?? -1);

                return $errorCode === 0;
            }
        } catch (\Exception $e) {
            Log::channel('payments')->error('Alfa-Bank reverse exception', [
                'transactionId' => $transactionId,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Возврат средств плательщику (частичный или полный):
     * refund.do
     */
    public function refundPayment(string $transactionId, float $amount): bool
    {
        if ($this->testMode && str_starts_with($transactionId, 'alfa_sb_')) {
            Log::channel('payments')->info('Alfa-Bank test refund.do mock refund success', [
                'transactionId' => $transactionId,
                'amount' => $amount,
            ]);

            return true;
        }

        $endpoint = $this->apiUrl.'/refund.do';
        $amountInKopecks = (int) round($amount * 100);

        $params = [
            'orderId' => $transactionId,
            'amount' => $amountInKopecks,
        ];

        if (! empty($this->token)) {
            $params['token'] = $this->token;
        } else {
            $params['userName'] = $this->userName;
            $params['password'] = $this->password;
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post($endpoint, $params);

            if ($response->successful()) {
                $json = $response->json();
                $errorCode = (int) ($json['errorCode'] ?? -1);

                return $errorCode === 0;
            }
        } catch (\Exception $e) {
            Log::channel('payments')->error('Alfa-Bank refund exception', [
                'transactionId' => $transactionId,
                'amount' => $amount,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Безакцептное / рекуррентное списание по сохранённому связочному токену карты.
     * paymentOrderBinding.do
     */
    public function chargeRecurring(
        string $bindingId,
        int $amountKopecks,
        array $metadata = []
    ): array {
        if ($this->testMode || empty($this->userName)) {
            if ($bindingId === 'invalid' || $bindingId === 'fail' || $bindingId === 'expired') {
                Log::channel('payments')->warning('Alfa-Bank sandbox recurring payment failed (mock failure)', [
                    'bindingId' => $bindingId,
                    'amountKopecks' => $amountKopecks,
                ]);

                return [
                    'success' => false,
                    'message' => 'Платёж по сохранённой карте отклонён банком',
                ];
            }

            $mockOrderId = 'alfa_recur_'.bin2hex(random_bytes(8));
            Log::channel('payments')->info('Alfa-Bank sandbox recurring payment authorized', [
                'bindingId' => $bindingId,
                'amountKopecks' => $amountKopecks,
                'mockOrderId' => $mockOrderId,
            ]);

            return [
                'success' => true,
                'gateway_transaction_id' => $mockOrderId,
                'order_id' => $mockOrderId,
                'amount_kopecks' => $amountKopecks,
            ];
        }

        $endpoint = $this->apiUrl.'/paymentOrderBinding.do';
        $orderNumber = 'sub_rec_'.($metadata['subscription_id'] ?? time()).'_'.time();

        $params = [
            'mdOrder' => $metadata['order_id'] ?? '',
            'bindingId' => $bindingId,
            'amount' => $amountKopecks,
            'currency' => $this->currencyCode,
        ];

        if (! empty($this->token)) {
            $params['token'] = $this->token;
        } else {
            $params['userName'] = $this->userName;
            $params['password'] = $this->password;
        }

        try {
            $response = Http::asForm()->timeout(30)->post($endpoint, $params);
            if ($response->successful()) {
                $json = $response->json();
                $errorCode = (int) ($json['errorCode'] ?? 0);
                if ($errorCode === 0) {
                    return [
                        'success' => true,
                        'gateway_transaction_id' => (string) ($json['orderId'] ?? $orderNumber),
                        'order_id' => (string) ($json['orderId'] ?? $orderNumber),
                        'payload' => $json,
                    ];
                }

                Log::channel('payments')->error('Alfa-Bank paymentOrderBinding API error', [
                    'errorCode' => $errorCode,
                    'errorMessage' => $json['errorMessage'] ?? '',
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('payments')->error('Alfa-Bank chargeRecurring exception', [
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'success' => false,
            'message' => 'Не удалось провести рекуррентный платёж через ЗАО «Альфа-Банк»',
        ];
    }

    /**
     * URL для получения вебхуков / нотификаций от Альфа-Банка.
     */
    public function getCallbackUrl(): string
    {
        return url('/api/v1/payments/alfabank/webhook');
    }
}
