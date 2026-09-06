<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\Transaction;
use App\Models\WalletTopup;
use App\Models\WebpayWebhookLog;
use App\Services\Finance\StudentBalanceService;
use App\Services\Payment\AlfaBankPaymentGateway;
use App\Services\Payment\PaymentService;
use App\Support\SecuritySanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlfaBankWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        AlfaBankPaymentGateway $gateway,
        PaymentService $paymentService
    ): JsonResponse {
        $clientIp = (string) $request->ip();
        $allowedIps = config('payments.alfabank.allowed_ips', config('payments.webpay.allowed_ips', []));
        $requireIpCheck = (bool) config('payments.webhook_require_ip_allowlist', false);

        // 1. Фильтрация IP-адресов
        if ($requireIpCheck && ! empty($allowedIps) && ! in_array($clientIp, $allowedIps, true)) {
            Log::channel('payments')->warning('webhook_blocked_ip', [
                'ip' => $clientIp,
                'allowed_ips' => $allowedIps,
            ]);

            $this->logWebhook($request, null, null, 403, 'Forbidden IP: '.$clientIp);

            return response()->json([
                'success' => false,
                'message' => 'Forbidden IP address.',
            ], 403);
        }

        $payload = $request->all();

        // 2. Проверка подписи WebPAY (если запрос на legacy роут webpay или передан заголовок/поле подписи)
        $isWebpayRequest = $request->is('*webpay*') || $request->has('ws_signature') || $request->hasHeader('X-WebPay-Signature');
        $requireSignature = (bool) config('payments.webhook_require_signature', false);

        if ($isWebpayRequest && $requireSignature) {
            $signature = (string) $request->header('X-WebPay-Signature', $payload['ws_signature'] ?? '');
            if (empty($signature)) {
                $this->logWebhook($request, null, null, 403, 'Missing signature.');

                return response()->json([
                    'success' => false,
                    'message' => 'Missing signature.',
                ], 403);
            }

            if (! $this->verifySignature($payload, $signature)) {
                $this->logWebhook($request, null, null, 403, 'Invalid signature.');

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature.',
                ], 403);
            }
        }

        // 3. Извлекаем данные об операции
        $transactionId = (string) ($payload['mdOrder'] ?? $payload['orderId'] ?? $payload['transaction_id'] ?? $payload['ws_order_id'] ?? '');
        $orderNumber = (string) ($payload['orderNumber'] ?? $payload['site_order_id'] ?? '');
        $operation = mb_strtolower((string) ($payload['operation'] ?? $payload['payment_type'] ?? $payload['status'] ?? 'approved'));
        $status = mb_strtolower((string) ($payload['status'] ?? $payload['payment_status'] ?? ''));

        Log::channel('payments')->info('payment_webhook_received', [
            'mdOrder' => $transactionId,
            'orderNumber' => $orderNumber,
            'operation' => $operation,
            'status' => $status,
            'ip' => $clientIp,
        ]);

        if (empty($transactionId) && empty($orderNumber)) {
            $this->logWebhook($request, $operation, null, 422, 'Missing transaction identifier.');

            return response()->json([
                'success' => false,
                'message' => 'Missing transaction identifier.',
            ], 422);
        }

        // 4. Обработка транзакции в БД
        DB::transaction(function () use ($transactionId, $orderNumber, $operation, $status, $paymentService) {
            $query = Transaction::query()->lockForUpdate();
            if (! empty($transactionId)) {
                $query->where('gateway_transaction_id', $transactionId);
            }
            if (! empty($orderNumber)) {
                $parts = explode('_', $orderNumber);
                if (count($parts) >= 2 && $parts[0] === 'lesson' && is_numeric($parts[1])) {
                    $query->orWhere('lesson_id', (int) $parts[1]);
                } elseif (is_numeric($orderNumber)) {
                    $query->orWhere('id', (int) $orderNumber);
                }
            }
            $tx = $query->first();

            $isSuccess = in_array($operation, ['deposited', 'success', 'completion', 'completed', 'successful'], true)
                || in_array($status, ['2', 'success', 'completed', 'successful'], true);
            $isAuthorized = in_array($operation, ['approved', 'hold', 'authorized', 'authorize'], true)
                || in_array($status, ['1', 'authorized', 'hold', 'pending'], true);
            $isVoided = in_array($operation, ['reversed', 'voided', 'void', 'cancelled'], true)
                || in_array($status, ['3', 'voided', 'cancelled', 'void'], true);
            $isFailed = in_array($operation, ['declined', 'failed', 'error'], true)
                || in_array($status, ['6', 'failed', 'declined', 'error'], true);

            if ($tx) {
                if ($isSuccess) {
                    if (in_array($tx->status, [Transaction::STATUS_AUTHORIZED, Transaction::STATUS_PENDING], true)) {
                        $paymentService->capturePendingPayment($tx);
                    }
                } elseif ($isAuthorized) {
                    if ($tx->status === Transaction::STATUS_PENDING) {
                        $tx->update(['status' => Transaction::STATUS_AUTHORIZED]);
                    }
                } elseif ($isVoided) {
                    if (in_array($tx->status, [Transaction::STATUS_AUTHORIZED, Transaction::STATUS_PENDING], true)) {
                        $tx->update(['status' => Transaction::STATUS_VOIDED]);

                        if ($tx->lesson && $tx->lesson->payment_status !== Lesson::PAYMENT_PAID) {
                            $tx->lesson->update(['payment_status' => Lesson::PAYMENT_UNPAID]);
                        }
                    }
                } elseif ($isFailed) {
                    if (in_array($tx->status, [Transaction::STATUS_AUTHORIZED, Transaction::STATUS_PENDING], true)) {
                        $tx->update(['status' => Transaction::STATUS_FAILED]);

                        if ($tx->lesson && $tx->lesson->payment_status !== Lesson::PAYMENT_PAID) {
                            $tx->lesson->update(['payment_status' => Lesson::PAYMENT_UNPAID]);
                        }
                    }
                }
            }

            // Проверка WalletTopup
            $topupQuery = WalletTopup::query()->lockForUpdate();
            if (! empty($transactionId)) {
                $topupQuery->where('gateway_transaction_id', $transactionId);
            }
            $topup = $topupQuery->first();

            if ($topup && $topup->status === 'pending') {
                if ($isSuccess) {
                    $claimed = WalletTopup::query()
                        ->whereKey($topup->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'success']);

                    if ($claimed > 0) {
                        app(StudentBalanceService::class)->credit(
                            balance: app(StudentBalanceService::class)->getOrCreate($topup->user_id),
                            amount: number_format((float) $topup->amount, 2, '.', ''),
                            currency: $topup->currency,
                            type: StudentBalanceLedgerEntry::TYPE_TOPUP,
                            meta: [
                                'source' => 'payment_webhook',
                                'gateway_transaction_id' => $transactionId,
                                'wallet_topup_id' => $topup->id,
                            ],
                        );
                    }
                } elseif ($isFailed) {
                    $topup->update(['status' => 'failed']);
                }
            }
        });

        $this->logWebhook($request, $operation, (string) ($transactionId ?: $orderNumber), 200);

        return response()->json([
            'success' => true,
            'message' => 'Payment webhook processed.',
        ]);
    }

    private function verifySignature(array $payload, string $signature): bool
    {
        $secret = config('payments.webpay.secret_key', config('payments.webhook_secret', ''));
        if (empty($secret)) {
            return false;
        }

        $batch = ($payload['batch_timestamp'] ?? '').
                 ($payload['currency_id'] ?? $payload['currency'] ?? '').
                 ($payload['amount'] ?? '').
                 ($payload['payment_method'] ?? '').
                 ($payload['order_id'] ?? '').
                 ($payload['site_order_id'] ?? '').
                 ($payload['transaction_id'] ?? '').
                 ($payload['payment_type'] ?? '').
                 ($payload['rrn'] ?? '').
                 $secret;

        return hash_equals(md5($batch), $signature);
    }

    private function logWebhook(Request $request, ?string $event, ?string $transactionId, int $statusCode, ?string $errorReason = null): void
    {
        try {
            WebpayWebhookLog::query()->create([
                'event' => $event,
                'transaction_id' => $transactionId,
                'payload' => SecuritySanitizer::maskSensitiveData($request->all()),
                'ip' => $request->ip(),
                'status_code' => $statusCode,
                'error_reason' => $errorReason,
            ]);
        } catch (\Exception $e) {
            Log::channel('payments')->error('failed_to_log_webhook', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
