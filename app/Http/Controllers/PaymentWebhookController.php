<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Webhooks\VerifiesWebhookSignature;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Общая логика проверки HMAC-SHA256 переиспользуется из трейта
     * (требование 10.7). Этот легаси-приёмник сохраняет собственный контракт:
     * заголовок `X-Webhook-Signature` (без префикса `sha256=`), IP-allowlist,
     * канал логов `payments` и коды ответов 503/403/422/200 — поэтому он не
     * использует template-method AbstractWebhookController, а вызывает только
     * низкоуровневый помощник hmacEquals().
     */
    use VerifiesWebhookSignature;

    public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
    {
        $gatewayType = config('payments.gateway');

        // ──────────────────────────────────────────────────────────
        // 1. Verify signature & IP BEFORE parsing body (security)
        // ──────────────────────────────────────────────────────────
        $signature = '';
        if ($gatewayType === 'bepaid' || $request->hasHeader('Content-Signature')) {
            $signature = $request->header('Content-Signature', '');
            $secret = config('payments.bepaid.secret_key', '');
        } else {
            $signature = $request->header('X-Webhook-Signature', '');
            $secret = config('payments.webhook_secret', '');
        }

        $allowedIps = config('payments.webhook_allowed_ips', []);
        $requireSignature = (bool) config('payments.webhook_require_signature', false);
        $requireIpAllowlist = (bool) config('payments.webhook_require_ip_allowlist', false);

        if ($requireSignature && $secret === '') {
            Log::channel('payments')->error('payment_webhook_missing_secret', [
                'ip' => $request->ip(),
                'received_at' => now('UTC')->toISOString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Webhook is not configured.'], 503);
        }

        if ($requireSignature && $signature === '') {
            Log::channel('payments')->warning('payment_webhook_missing_signature', [
                'ip' => $request->ip(),
                'received_at' => now('UTC')->toISOString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 403);
        }

        if ($secret !== '') {
            if ($signature === '' || ! $this->hmacEquals($request->getContent(), (string) $signature, (string) $secret)) {
                Log::channel('payments')->warning('payment_webhook_invalid_signature', [
                    'ip' => $request->ip(),
                    'received_at' => now('UTC')->toISOString(),
                ]);

                return response()->json(['success' => false, 'message' => 'Invalid signature.'], 403);
            }
        }

        if ($requireIpAllowlist && empty($allowedIps)) {
            Log::channel('payments')->error('payment_webhook_missing_ip_allowlist', [
                'ip' => $request->ip(),
                'received_at' => now('UTC')->toISOString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Webhook is not configured.'], 503);
        }

        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps, true)) {
            Log::channel('payments')->warning('payment_webhook_blocked_ip', [
                'ip' => $request->ip(),
                'received_at' => now('UTC')->toISOString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        // ──────────────────────────────────────────────────────────
        // 2. Signature & IP verified — now safe to parse payload
        // ──────────────────────────────────────────────────────────
        $event = null;
        $transactionId = null;
        $errorReason = null;

        $payload = $request->all();
        if (isset($payload['transaction'])) {
            $txData = $payload['transaction'];
            $transactionId = $txData['checkout_token'] ?? $txData['token'] ?? $txData['uid'] ?? null;
            $status = $txData['status'] ?? null;
            if ($status === 'successful') {
                $event = 'payment.success';
            } elseif (in_array($status, ['failed', 'declined', 'error'], true)) {
                $event = 'payment.failed';
                $errorReason = $txData['message'] ?? $txData['error'] ?? null;
            }
        } elseif (isset($payload['checkout'])) {
            $checkoutData = $payload['checkout'];
            $transactionId = $checkoutData['token'] ?? null;
            $status = $checkoutData['status'] ?? null;
            if ($status === 'successful') {
                $event = 'payment.success';
            } elseif (in_array($status, ['failed', 'declined', 'error'], true)) {
                $event = 'payment.failed';
                $errorReason = $checkoutData['message'] ?? null;
            }
        } else {
            $event = $payload['event'] ?? $payload['type'] ?? null;
            $transactionId = $payload['gateway_transaction_id'] ?? $payload['transaction_id'] ?? null;
            $errorReason = $payload['error_message'] ?? $payload['message'] ?? null;
        }

        Log::channel('payments')->info('payment_webhook_received', [
            'event' => $event,
            'transaction_id' => $transactionId,
            'ip' => $request->ip(),
            'received_at' => now('UTC')->toISOString(),
        ]);

        if ($event === null || $transactionId === null) {
            $this->logWebhook($request, $event, $transactionId, 422, 'Missing event or transaction_id.');
            return response()->json([
                'success' => false,
                'message' => 'Missing event or transaction_id.',
            ], 422);
        }

        // Выполняем действие в соответствии с событием
        $closure = match ($event) {
            'payment.success', 'payment.completed' => fn() => \Illuminate\Support\Facades\DB::transaction(function () use ($transactionId, $paymentService) {
                // 1. Проверяем оплату за уроки
                $tx = \App\Models\Transaction::query()->where('gateway_transaction_id', $transactionId)->first();
                if ($tx && $tx->status === \App\Models\Transaction::STATUS_PENDING) {
                    $paymentService->capturePendingPayment($tx);
                    return;
                }

                // 2. Проверяем пополнение кошелька
                $topup = \App\Models\WalletTopup::query()->where('gateway_transaction_id', $transactionId)->first();
                if ($topup && $topup->status === 'pending') {
                    app(\App\Services\Finance\StudentBalanceService::class)->credit(
                        balance: app(\App\Services\Finance\StudentBalanceService::class)->getOrCreate($topup->user_id),
                        amount: number_format((float) $topup->amount, 2, '.', ''),
                        currency: $topup->currency,
                        type: \App\Models\StudentBalanceLedgerEntry::TYPE_TOPUP,
                        meta: [
                            'source' => 'webhook',
                            'gateway_transaction_id' => $transactionId,
                            'wallet_topup_id' => $topup->id,
                        ],
                    );
                    $topup->update(['status' => 'success']);
                }
            }),
            'payment.failed' => fn() => \Illuminate\Support\Facades\DB::transaction(function () use ($transactionId) {
                // 1. Проверяем оплату за уроки
                $tx = \App\Models\Transaction::query()->where('gateway_transaction_id', $transactionId)->first();
                if ($tx && $tx->status === \App\Models\Transaction::STATUS_PENDING) {
                    $tx->update(['status' => \App\Models\Transaction::STATUS_FAILED]);
                    $tx->lesson->update(['payment_status' => \App\Models\Lesson::PAYMENT_UNPAID]);
                    return;
                }

                // 2. Проверяем пополнение кошелька
                $topup = \App\Models\WalletTopup::query()->where('gateway_transaction_id', $transactionId)->first();
                if ($topup && $topup->status === 'pending') {
                    $topup->update(['status' => 'failed']);
                }
            }),
            default => fn() => null,
        };
        $closure();

        $this->logWebhook($request, $event, $transactionId, 200, $errorReason);

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed.',
        ]);
    }

    private function logWebhook(Request $request, ?string $event, ?string $transactionId, int $statusCode, ?string $errorReason = null): void
    {
        try {
            \App\Models\BepaidWebhookLog::query()->create([
                'event' => $event,
                'transaction_id' => $transactionId,
                'payload' => $request->all(),
                'ip' => $request->ip(),
                'status_code' => $statusCode,
                'error_reason' => $errorReason,
            ]);
        } catch (\Exception $e) {
            Log::channel('payments')->error('failed_to_log_webhook_to_db', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
