<?php

declare(strict_types=1);

return [
    'sync_enabled' => (bool) env('SITE_ADMIN_SYNC_ENABLED', false),
    'name' => env('SITE_ADMIN_NAME', 'Технический администратор'),
    'email' => env('SITE_ADMIN_EMAIL'),
    'password' => env('SITE_ADMIN_PASSWORD'),
];
