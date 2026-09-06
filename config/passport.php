<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Passport Configuration (RS256)
|--------------------------------------------------------------------------
|
| Ключи для подписи access-токенов алгоритмом RS256.
|
| Passport v13 принимает содержимое ключа (PEM-строку) через
| PASSPORT_PRIVATE_KEY / PASSPORT_PUBLIC_KEY.
|
| Для удобства поддерживаем также пути к файлам через
| PASSPORT_PRIVATE_KEY_PATH / PASSPORT_PUBLIC_KEY_PATH:
| содержимое файла читается здесь и передаётся в Passport.
|
| Для zero-downtime ротации (требование 3.7) задайте
| PASSPORT_PREVIOUS_PUBLIC_KEY_PATH — JwksController опубликует
| оба публичных ключа одновременно.
|
*/

/**
 * Читает PEM-ключ: сначала из ENV-переменной с содержимым,
 * затем из файла по пути из ENV-переменной с суффиксом _PATH.
 */
$readKey = static function (string $envContent, string $envPath): ?string {
    // Приоритет 1: содержимое ключа задано напрямую в ENV
    $content = env($envContent);
    if ($content !== null && $content !== '') {
        return str_replace('\\n', "\n", (string) $content);
    }

    // Приоритет 2: путь к файлу задан в ENV
    $path = env($envPath);
    if ($path !== null && $path !== '' && file_exists((string) $path)) {
        return file_get_contents((string) $path) ?: null;
    }

    // Приоритет 3: дефолтный путь в storage/
    $defaultPath = storage_path(str_contains($envPath, 'PRIVATE') ? 'oauth-private.key' : 'oauth-public.key');
    if (file_exists($defaultPath)) {
        return file_get_contents($defaultPath) ?: null;
    }

    return null;
};

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    */

    'guard' => 'web',

    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys (RS256)
    |--------------------------------------------------------------------------
    |
    | Passport использует эти ключи для подписи access-токенов (RS256).
    | Задайте содержимое ключа через PASSPORT_PRIVATE_KEY / PASSPORT_PUBLIC_KEY
    | или путь к файлу через PASSPORT_PRIVATE_KEY_PATH / PASSPORT_PUBLIC_KEY_PATH.
    |
    */

    'private_key' => $readKey('PASSPORT_PRIVATE_KEY', 'PASSPORT_PRIVATE_KEY_PATH'),

    'public_key' => $readKey('PASSPORT_PUBLIC_KEY', 'PASSPORT_PUBLIC_KEY_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Key File Paths (используются JwksController для ротации)
    |--------------------------------------------------------------------------
    |
    | Эти пути используются JwksController для публикации ключей в JWKS.
    | Для zero-downtime ротации задайте PASSPORT_PREVIOUS_PUBLIC_KEY_PATH.
    |
    */

    'private_key_path' => env('PASSPORT_PRIVATE_KEY_PATH', storage_path('oauth-private.key')),

    'public_key_path' => env('PASSPORT_PUBLIC_KEY_PATH', storage_path('oauth-public.key')),

    'previous_public_key_path' => env('PASSPORT_PREVIOUS_PUBLIC_KEY_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Passport Database Connection
    |--------------------------------------------------------------------------
    */

    'connection' => env('PASSPORT_CONNECTION'),

];
