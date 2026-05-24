<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
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

        if ($secret !== '' && ! $this->verifySignature($request->getContent(), (string) $signature, (string) $secret)) {
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
            'payment.success', 'payment.completed' => Log::channel('payments')->info('payment_webhook_success', [
                'transaction_id' => $transactionId,
            ]),
            'payment.failed' => Log::channel('payments')->info('payment_webhook_failed', [
                'transaction_id' => $transactionId,
            ]),
            'payment.refunded' => Log::channel('payments')->info('payment_webhook_refunded', [
                'transaction_id' => $transactionId,
            ]),
            default => Log::channel('payments')->info('payment_webhook_unknown_event', [
                'event' => $event,
                'transaction_id' => $transactionId,
            ]),
        };

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed.',
        ]);
    }

    private function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
