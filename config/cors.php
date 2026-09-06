<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers.
    |
    */

    'paths' => ['api/*', 'webhooks/*', 'metrics'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        env('APP_URL', 'https://edusfera.by'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN', 'Accept', 'X-Idempotency-Key'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
