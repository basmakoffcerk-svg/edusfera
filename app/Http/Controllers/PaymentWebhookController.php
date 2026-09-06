<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Payment\AlfaBankPaymentGateway;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        AlfaBankPaymentGateway $gateway,
        PaymentService $paymentService
    ): JsonResponse {
        return app(AlfaBankWebhookController::class)($request, $gateway, $paymentService);
    }
}
