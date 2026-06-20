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
        $signature = $request->header('X-Webhook-Signature', '');
        $secret = config('payments.webhook_secret', '');
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

        if ($requireIpAllowlist && empty($allowedIps)) {
            Log::channel('payments')->error('payment_webhook_missing_ip_allowlist', [
                'ip' => $request->ip(),
                'received_at' => now('UTC')->toISOString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Webhook is not configured.'], 503);
        }

        if ($secret !== '' && ! $this->hmacEquals($request->getContent(), (string) $signature, (string) $secret)) {
            Log::channel('payments')->warning('payment_webhook_invalid_signature', [
                'ip' => $request->ip(),
                'received_at' => now('UTC')->toISOString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 403);
        }

        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps, true)) {
            Log::channel('payments')->warning('payment_webhook_blocked_ip', [
                'ip' => $request->ip(),
                'received_at' => now('UTC')->toISOString(),
            ]);

            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? $payload['type'] ?? null;
        $transactionId = $payload['gateway_transaction_id'] ?? $payload['transaction_id'] ?? null;

        Log::channel('payments')->info('payment_webhook_received', [
            'event' => $event,
            'transaction_id' => $transactionId,
            'ip' => $request->ip(),
            'received_at' => now('UTC')->toISOString(),
        ]);

        if ($event === null || $transactionId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Missing event or transaction_id.',
            ], 422);
        }

        match ($event) {
            'payment.success', 'payment.completed' => function () use ($transactionId, $paymentService) {
                Log::channel('payments')->info('payment_webhook_success', ['transaction_id' => $transactionId]);
                $transaction = \App\Models\Transaction::query()->where('gateway_transaction_id', $transactionId)->first();
                if ($transaction && $transaction->status === \App\Models\Transaction::STATUS_PENDING) {
                    $paymentService->capturePendingPayment($transaction);
                }
            },
            'payment.failed' => function () use ($transactionId) {
                Log::channel('payments')->info('payment_webhook_failed', ['transaction_id' => $transactionId]);
                $transaction = \App\Models\Transaction::query()->where('gateway_transaction_id', $transactionId)->first();
                if ($transaction && $transaction->status === \App\Models\Transaction::STATUS_PENDING) {
                    $transaction->update(['status' => \App\Models\Transaction::STATUS_FAILED]);
                    $transaction->lesson->update(['payment_status' => \App\Models\Lesson::PAYMENT_UNPAID]);
                }
            },
            'payment.refunded' => function () use ($transactionId) {
                Log::channel('payments')->info('payment_webhook_refunded', ['transaction_id' => $transactionId]);
            },
            default => function () use ($event, $transactionId) {
                Log::channel('payments')->info('payment_webhook_unknown_event', ['event' => $event, 'transaction_id' => $transactionId]);
            },
        };

        // Call the matched closure
        $closure = match ($event) {
            'payment.success', 'payment.completed' => fn() => 
                tap(\App\Models\Transaction::query()->where('gateway_transaction_id', $transactionId)->first(), function ($tx) use ($paymentService) {
                    if ($tx && $tx->status === \App\Models\Transaction::STATUS_PENDING) {
                        $paymentService->capturePendingPayment($tx);
                    }
                }),
            'payment.failed' => fn() => 
                tap(\App\Models\Transaction::query()->where('gateway_transaction_id', $transactionId)->first(), function ($tx) {
                    if ($tx && $tx->status === \App\Models\Transaction::STATUS_PENDING) {
                        $tx->update(['status' => \App\Models\Transaction::STATUS_FAILED]);
                        $tx->lesson->update(['payment_status' => \App\Models\Lesson::PAYMENT_UNPAID]);
                    }
                }),
            default => fn() => null,
        };
        $closure();

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed.',
        ]);
    }
}
