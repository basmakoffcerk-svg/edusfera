<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
        // Per-client секрет для HMAC-SHA256 проверки входящих webhook'ов
        // AI-сервиса на POST /webhooks/ai/recommendations (требование 10).
        'webhook_secret' => env('AI_WEBHOOK_SECRET'),
        'default_provider' => env('AI_DEFAULT_PROVIDER', 'google'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'lite_model' => env('GEMINI_LITE_MODEL', 'gemini-flash-latest'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'proxy' => env('GEMINI_PROXY'),
        'use_xbox_dns' => env('GEMINI_USE_XBOX_DNS', true),
    ],

    'ledger' => [
        'url' => env('LEDGER_URL', 'http://ledger:8080'),
        'enabled' => env('LEDGER_ENABLED', true),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    'analytics' => [
        'yandex_metrika_id' => env('YANDEX_METRIKA_ID', ''),
        'google_tag_id' => env('GOOGLE_TAG_ID', ''),
    ],

];
