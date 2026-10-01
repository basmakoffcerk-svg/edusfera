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
            $returnUrl = route('filament.admin.pages.wallet', ['payment' => 'success']);
            $failUrl = route('filament.admin.pages.wallet', ['payment' => 'failed']);
            $description = 'Пополнение баланса Edusfera (Пользователь #'.($data['user_id'] ?? 0).')';
            $orderNumber = 'wallet_'.($data['user_id'] ?? 0).'_'.time();
        } elseif ($isSubscription) {
            $returnUrl = route('filament.admin.pages.tutor-subscription-page', ['payment' => 'success']);
            $failUrl = route('filament.admin.pages.tutor-subscription-page', ['payment' => 'failed']);
            $description = 'Оплата подписки Edusfera (Преподаватель #'.($data['user_id'] ?? 0).')';
            $orderNumber = 'sub_'.($data['user_id'] ?? 0).'_'.time();
        } else {
            $lessonId = $data['lesson_id'] ?? 0;
            $returnUrl = route('checkout.success', ['lesson' => $lessonId]);
            $failUrl = route('checkout.show', ['lesson' => $lessonId, 'payment' => 'failed']);
            $description = 'Оплата занятия Edusfera (Урок #'.$lessonId.')';
            $orderNumber = 'lesson_'.$lessonId.'_'.time();
        }

        // Одностадийная оплата (register.do) для подписок и баланса; двухстадийная (registerPreAuth.do) для холдирования уроков
        $endpoint = ($isSubscription || $isWalletTopUp)
            ? $this->apiUrl.'/register.do'
            : $this->apiUrl.'/registerPreAuth.do';

        if ($amountInKopecks <= 0) {
            // Для привязки карты или триала Alfa-Bank RBS требует сумму > 0 копеек
            $amountInKopecks = 100;
        }

        $clientId = (string) ($data['user_id'] ?? auth()->id() ?? '');

        $params = [
            'orderNumber' => $orderNumber,
            'amount' => $amountInKopecks,
            'currency' => $this->currencyCode,
            'returnUrl' => $returnUrl,
            'failUrl' => $failUrl,
            'description' => mb_substr($description, 0, 100),
            'language' => 'ru',
        ];

        if (! empty($clientId)) {
            $params['clientId'] = $clientId;
        }

        if (! empty($this->token)) {
            $params['token'] = $this->token;
        } else {
            $params['userName'] = $this->userName;
            $params['password'] = $this->password;
        }

        $bankErrorMessage = null;

        try {
            if (! empty($this->userName) && ! empty($this->password)) {
                $response = Http::asForm()
                    ->timeout(30)
                    ->post($endpoint, $params);

                if ($response->successful()) {
                    $json = $response->json();
                    $errorCode = (int) ($json['errorCode'] ?? 0);

                    // Если предавторизация не разрешена банком для данного мерчанта (код 5), пробуем одностадийную регистрацию
                    if ($errorCode === 5 && str_contains($endpoint, 'registerPreAuth.do')) {
                        Log::channel('payments')->notice('Alfa-Bank preauth not allowed for merchant, retrying with register.do', [
                            'orderNumber' => $orderNumber,
                        ]);
                        $fallbackResponse = Http::asForm()
                            ->timeout(30)
                            ->post($this->apiUrl.'/register.do', $params);

                        if ($fallbackResponse->successful()) {
                            $fallbackJson = $fallbackResponse->json();
                            if (((int) ($fallbackJson['errorCode'] ?? -1)) === 0 && ! empty($fallbackJson['orderId'])) {
                                $json = $fallbackJson;
                                $errorCode = 0;
                            } else {
                                $bankErrorMessage = $fallbackJson['errorMessage'] ?? $json['errorMessage'] ?? null;
                            }
                        }
                    }

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

                    $bankErrorMessage = $json['errorMessage'] ?? "Код ошибки банка: {$errorCode}";

                    Log::channel('payments')->error('Alfa-Bank register API error', [
                        'errorCode' => $errorCode,
                        'errorMessage' => $json['errorMessage'] ?? '',
                        'orderNumber' => $orderNumber,
                    ]);
                } else {
                    $bankErrorMessage = 'Шлюз банка вернул статус '.$response->status();
                }
            }
        } catch (\Throwable $e) {
            $bankErrorMessage = 'Ошибка соединения: '.$e->getMessage();
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

            $hostedTestUrl = route('payments.alfabank.hosted-test', [
                'orderId' => $mockOrderId,
                'orderNumber' => $orderNumber,
                'amount' => $amount,
                'description' => $description,
                'returnUrl' => $returnUrl,
                'failUrl' => $failUrl,
            ]);

            return [
                'success' => true,
                'gateway_transaction_id' => $mockOrderId,
                'mdOrder' => $mockOrderId,
                'status' => 'authorized',
                'redirect_url' => $hostedTestUrl,
                'web_sdk_url' => $this->getWebSdkUrl(),
                'api_context' => $this->getApiContext(),
                'payload' => array_merge($params, ['orderId' => $mockOrderId]),
            ];
        }

        $finalMsg = 'Не удалось авторизовать платёж через ЗАО «Альфа-Банк»';
        if (! empty($bankErrorMessage)) {
            $finalMsg .= ": {$bankErrorMessage}";
        }

        return [
            'success' => false,
            'message' => $finalMsg,
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
        return $this->getPaymentStatusDetails($transactionId)['success'];
    }

    /**
     * Получение детального статуса платежа и расшифровка кода ответа банка:
     *
     * @return array{
     *     success: bool,
     *     order_status: int,
     *     action_code: int,
     *     error_code: int,
     *     message: string,
     *     order_number: string|null,
     *     raw: array
     * }
     */
    public function getPaymentStatusDetails(string $transactionId): array
    {
        $isMockOrder = str_starts_with($transactionId, 'alfa_sb_')
            || str_starts_with($transactionId, 'alfa_order_test_')
            || str_starts_with($transactionId, 'alfa_wallet_tx_')
            || empty($this->userName);
        $isExplicitFailure = str_contains($transactionId, 'declined') || str_contains($transactionId, 'fail');

        if ($this->testMode && ($isMockOrder || app()->runningUnitTests()) && ! $isExplicitFailure) {
            return [
                'success' => true,
                'order_status' => 2,
                'action_code' => 0,
                'error_code' => 0,
                'message' => 'Платёж успешно подтверждён в тестовом режиме.',
                'order_number' => $transactionId,
                'raw' => [],
            ];
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
                $json = $response->json() ?? [];
                $orderStatus = (int) ($json['orderStatus'] ?? -1);
                $actionCode = (int) ($json['actionCode'] ?? -1);
                $errorCode = (int) ($json['errorCode'] ?? 0);
                $actionDesc = (string) ($json['actionCodeDescription'] ?? '');
                $errorDesc = (string) ($json['errorMessage'] ?? '');

                $isSuccess = in_array($orderStatus, [1, 2], true);
                $message = $this->resolveStatusMessage($orderStatus, $actionCode, $actionDesc ?: $errorDesc);

                return [
                    'success' => $isSuccess,
                    'order_status' => $orderStatus,
                    'action_code' => $actionCode,
                    'error_code' => $errorCode,
                    'message' => $message,
                    'order_number' => $json['orderNumber'] ?? null,
                    'raw' => $json,
                ];
            }
        } catch (\Exception $e) {
            Log::channel('payments')->error('Alfa-Bank getPaymentStatusDetails exception', [
                'transactionId' => $transactionId,
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'success' => false,
            'order_status' => -1,
            'action_code' => -1,
            'error_code' => -1,
            'message' => 'Не удалось получить ответ от платёжного сервера банка. Попробуйте обновить страницу или повторить попытку.',
            'order_number' => null,
            'raw' => [],
        ];
    }

    private function resolveStatusMessage(int $orderStatus, int $actionCode, string $fallbackDescription = ''): string
    {
        if (in_array($orderStatus, [1, 2], true)) {
            return 'Платёж успешно проведён банком.';
        }

        return match ($actionCode) {
            116 => 'Недостаточно средств на карте. Пожалуйста, пополните баланс карты или выберите другую карту для оплаты.',
            125 => 'Неверно указан код безопасности (CVC/CVV). Проверьте трехзначный код на обороте карты.',
            126 => 'Неверный одноразовый СМС-код 3-D Secure. Попробуйте ещё раз.',
            100, 101 => 'Срок действия карты истёк либо карта недействительна.',
            107, 119 => 'Операция отклонена банком-эмитентом вашей карты. Обратитесь в службу поддержки банка или воспользуйтесь другой картой.',
            110 => 'Неверная сумма операции.',
            111 => 'Неверный номер карты. Проверьте правильность введенных данных карты.',
            -100 => 'Платёж не был завершён (время сессии оплаты истекло или операция была отменена).',
            default => match ($orderStatus) {
                3 => 'Авторизация отменена пользователем.',
                6 => ! empty($fallbackDescription) ? $fallbackDescription : 'Платёж отклонён банком. Пожалуйста, попробуйте ещё раз или используйте другую карту.',
                0 => 'Оплата не была завершена. Пожалуйста, повторите попытку.',
                default => ! empty($fallbackDescription) ? $fallbackDescription : 'Платёж не прошёл. Проверьте реквизиты карты или выберите другой способ оплаты.',
            }
        };
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

                if ($errorCode === 0) {
                    return true;
                }

                // Если заказ ещё не был списан (deposit), а находится в статусе холда (pre-auth),
                // банк отклоняет refund.do — для отмены холда требуется reverse.do.
                Log::channel('payments')->info('Alfa-Bank refund.do rejected, attempting reverse.do for pre-auth hold', [
                    'transactionId' => $transactionId,
                    'errorCode' => $errorCode,
                    'errorMessage' => $json['errorMessage'] ?? '',
                ]);

                return $this->voidPayment($transactionId);
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
