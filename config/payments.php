<?php

$appEnv = (string) env('APP_ENV', 'production');
$isLocalLike = in_array($appEnv, ['local', 'testing'], true);
$isProduction = $appEnv === 'production';

return [
    'commission_rate' => 0.15,
    'acquiring_rate' => 0.022,
    'acquiring_fixed' => 0.30,
    'gateway' => env('PAYMENT_GATEWAY', $isLocalLike ? 'mock' : 'disabled'),
    'currency' => 'BYN',
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', ''),
    'webhook_allowed_ips' => array_filter(explode(',', env('PAYMENT_WEBHOOK_ALLOWED_IPS', ''))),
    'webhook_require_signature' => (bool) env('PAYMENT_WEBHOOK_REQUIRE_SIGNATURE', $isProduction),
    'webhook_require_ip_allowlist' => (bool) env('PAYMENT_WEBHOOK_REQUIRE_IP_ALLOWLIST', $isProduction),
];
