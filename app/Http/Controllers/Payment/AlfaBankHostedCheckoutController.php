<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlfaBankHostedCheckoutController extends Controller
{
    /**
     * Render the authentic Alfa-Bank hosted payment page for sandbox/test environments.
     */
    public function show(Request $request): View
    {
        $orderId = (string) $request->query('orderId', 'alfa_sb_'.bin2hex(random_bytes(8)));
        $orderNumber = (string) $request->query('orderNumber', 'SUB-'.strtoupper(bin2hex(random_bytes(4))));
        $amount = (float) $request->query('amount', 40.00);
        $description = (string) $request->query('description', 'Оплата подписки на платформе Edusfera.by');
        $returnUrl = (string) $request->query('returnUrl', route('filament.admin.pages.tutor-subscription-page'));
        $failUrl = (string) $request->query('failUrl', route('filament.admin.pages.tutor-subscription-page'));

        return view('payments.alfabank-hosted-checkout', [
            'orderId' => $orderId,
            'orderNumber' => $orderNumber,
            'amount' => $amount,
            'description' => $description,
            'returnUrl' => $returnUrl,
            'failUrl' => $failUrl,
        ]);
    }
}
