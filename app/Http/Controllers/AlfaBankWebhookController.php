<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Subscription\Enums\InvoiceStatus;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionInvoice;
use App\Domain\Subscription\Services\SubscriptionService;
use App\Models\Lesson;
use App\Models\PaymentWebhookLog;
use App\Models\StudentBalanceLedgerEntry;
use App\Models\Transaction;
use App\Models\WalletTopup;
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
        $allowedIps = config('payments.webhook_allowed_ips', config('payments.alfabank.allowed_ips', []));
        $requireIpCheck = (bool) config('payments.webhook_require_ip_allowlist', false);

        // 1. Фильтрация IP-адресов
        if ($requireIpCheck) {
            if (empty($allowedIps) || ! in_array($clientIp, $allowedIps, true)) {
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
        }

        $payload = $request->all();

        // 2. Проверка подписи/контрольной суммы (если включена)
        $requireSignature = (bool) config('payments.webhook_require_signature', false);

        if ($requireSignature) {
            $signature = (string) ($request->header('X-Payment-Signature')
                ?: $request->header('X-Signature')
                ?: ($payload['signature'] ?? ''));

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

        $isSuccess = in_array($operation, ['deposited', 'success', 'completion', 'completed', 'successful'], true)
            || in_array($status, ['2', 'success', 'completed', 'successful'], true);
        $isAuthorized = in_array($operation, ['approved', 'hold', 'authorized', 'authorize'], true)
            || in_array($status, ['1', 'authorized', 'hold', 'pending'], true);
        $isVoided = in_array($operation, ['reversed', 'voided', 'void', 'cancelled'], true)
            || in_array($status, ['3', 'voided', 'cancelled', 'void'], true);
        $isFailed = in_array($operation, ['declined', 'failed', 'error'], true)
            || in_array($status, ['6', 'failed', 'declined', 'error'], true);

        // 4. Верификация через шлюз Альфа-Банка для предотвращения несанкционированных вызовов
        if (! empty($transactionId) && ($isSuccess || $isAuthorized)) {
            if (! $gateway->verifyPayment($transactionId)) {
                Log::channel('payments')->warning('alfabank_webhook_gateway_verification_failed', [
                    'transaction_id' => $transactionId,
                    'operation' => $operation,
                    'ip' => $clientIp,
                ]);

                $this->logWebhook($request, $operation, $transactionId, 400, 'Gateway verification failed');

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed at gateway.',
                ], 400);
            }
        }

        // 5. Обработка транзакции в БД
        DB::transaction(function () use ($transactionId, $orderNumber, $paymentService, $isSuccess, $isAuthorized, $isVoided, $isFailed, $gateway) {
            $tx = null;
            if (! empty($transactionId)) {
                $tx = Transaction::query()->lockForUpdate()->where('gateway_transaction_id', $transactionId)->first();
            }

            if (! $tx && ! empty($orderNumber)) {
                $parts = explode('_', $orderNumber);
                if (count($parts) >= 2 && $parts[0] === 'lesson' && is_numeric($parts[1])) {
                    $tx = Transaction::query()->lockForUpdate()->where('lesson_id', (int) $parts[1])->first();
                } elseif (is_numeric($orderNumber)) {
                    $tx = Transaction::query()->lockForUpdate()->where('id', (int) $orderNumber)->first();
                }
            }

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

            // Проверка WalletTopup — строго по transaction_id
            $topup = null;
            if (! empty($transactionId)) {
                $topup = WalletTopup::query()->lockForUpdate()->where('gateway_transaction_id', $transactionId)->first();
            }

            if ($topup && $topup->status === 'pending') {
                if ($isSuccess) {
                    $claimed = WalletTopup::query()
                        ->whereKey($topup->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'success']);

                    if ($claimed > 0) {
                        // Депозитируем платеж в шлюзе, если это холд
                        if (! empty($transactionId)) {
                            $gateway->capturePayment($transactionId, (float) $topup->amount);
                        }

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

            // Обработка подписок репетиторов (заказы вида sub_{userId}_{time})
            if (! empty($orderNumber)) {
                $subParts = explode('_', $orderNumber);
                if (count($subParts) >= 2 && $subParts[0] === 'sub' && is_numeric($subParts[1])) {
                    $userId = (int) $subParts[1];
                    $sub = Subscription::query()->lockForUpdate()->where('tutor_id', $userId)->first();
                    if ($sub && $isSuccess) {
                        // Проверка на идемпотентность: был ли этот заказ или транзакция уже оплачены ранее
                        $alreadyProcessed = SubscriptionInvoice::where('subscription_id', $sub->id)
                            ->where('status', InvoiceStatus::PAID)
                            ->where(function ($query) use ($orderNumber, $transactionId) {
                                $query->where('payload->order_number', $orderNumber);
                                if (! empty($transactionId)) {
                                    $query->orWhere('payload->gateway_transaction_id', $transactionId);
                                }
                            })
                            ->exists();

                        if (! $alreadyProcessed) {
                            if (! empty($transactionId)) {
                                try {
                                    $gateway->capturePayment($transactionId, (float) ($sub->plan->monthlyPriceByn()));
                                } catch (\Throwable $e) {
                                    Log::error('Alfa-Bank subscription webhook capture failed', [
                                        'orderNumber' => $orderNumber,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }

                            $subService = app(SubscriptionService::class);
                            $invoice = SubscriptionInvoice::where('subscription_id', $sub->id)
                                ->where('status', InvoiceStatus::PENDING)
                                ->latest()
                                ->first();

                            if (! $invoice) {
                                $invoice = $subService->createInvoice($sub, $sub->plan, 1);
                            }

                            if ($invoice && $invoice->status !== InvoiceStatus::PAID) {
                                $subService->recordPayment($invoice, 'card', [
                                    'bank' => 'Альфа-Банк (Alfa-Bank)',
                                    'gateway_transaction_id' => $transactionId,
                                    'order_number' => $orderNumber,
                                ]);
                            }
                        }
                    }
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
        $secret = config('payments.webhook_secret', '');
        if (empty($secret)) {
            return false;
        }

        $batch = ($payload['batch_timestamp'] ?? '').
                 ($payload['currency_id'] ?? $payload['currency'] ?? '').
                 ($payload['amount'] ?? '').
                 ($payload['payment_method'] ?? '').
                 ($payload['order_id'] ?? '').
                 ($payload['site_order_id'] ?? $payload['orderNumber'] ?? '').
                 ($payload['transaction_id'] ?? $payload['mdOrder'] ?? '').
                 ($payload['payment_type'] ?? $payload['operation'] ?? '').
                 ($payload['rrn'] ?? '').
                 $secret;

        return hash_equals(md5($batch), $signature);
    }

    private function logWebhook(Request $request, ?string $event, ?string $transactionId, int $statusCode, ?string $errorReason = null): void
    {
        try {
            PaymentWebhookLog::query()->create([
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
