<?php

$appEnv = (string) env('APP_ENV', 'production');
$isLocalLike = in_array($appEnv, ['local', 'testing'], true);
$isProduction = $appEnv === 'production';

return [
    'commission_rate' => (float) env('PLATFORM_COMMISSION_RATE', 0.00),
    'gateway' => env('PAYMENT_GATEWAY', $isLocalLike ? 'mock' : 'alfa'),
    'alfabank' => [
        'user_name' => env('ALFABANK_USER_NAME', env('ALFABANK_API_USER', '')),
        'password' => env('ALFABANK_PASSWORD', env('ALFABANK_API_PASSWORD', '')),
        'token' => env('ALFABANK_TOKEN', ''),
        'test_mode' => (bool) env('ALFABANK_TEST_MODE', true),
        'api_url' => env('ALFABANK_API_URL', 'https://web.rbsuat.com/ab_by/rest'),
        'checkout_url' => env('ALFABANK_CHECKOUT_URL', 'https://web.rbsuat.com/ab_by/rest'),
        'web_sdk_url' => env(
            'ALFABANK_WEB_SDK_URL',
            (bool) env('ALFABANK_TEST_MODE', true)
                ? 'https://abby.rbsuat.com/payment/modules/multiframe/main.js'
                : 'https://ecom.alfabank.by/payment/modules/multiframe/main.js'
        ),
        'api_context' => env('ALFABANK_API_CONTEXT', '/payment'),
        'currency_code' => env('ALFABANK_CURRENCY_CODE', '933'),
        'hold_period_days' => (int) env('ALFABANK_HOLD_PERIOD_DAYS', 7),
        'allowed_ips' => array_filter(explode(',', env('ALFABANK_ALLOWED_IPS', ''))),
    ],
    'currency' => 'BYN',
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', ''),
    'webhook_allowed_ips' => array_filter(explode(',', env('PAYMENT_WEBHOOK_ALLOWED_IPS', '178.163.225.84'))),
    'webhook_require_signature' => (bool) env('PAYMENT_WEBHOOK_REQUIRE_SIGNATURE', true),
    'webhook_require_ip_allowlist' => (bool) env('PAYMENT_WEBHOOK_REQUIRE_IP_ALLOWLIST', $isProduction),
];
