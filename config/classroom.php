<?php

declare(strict_types=1);

return [
    'enabled' => env('CLASSROOM_ENABLED', true),
    'livekit_host' => env('LIVEKIT_URL', env('LIVEKIT_HOST', 'wss://edusfera-iae194s8.livekit.cloud')),
    'livekit_api_key' => env('LIVEKIT_API_KEY', env('LIVEKIT_KEY', '')),
    'livekit_api_secret' => env('LIVEKIT_API_SECRET', env('LIVEKIT_SECRET', '')),
    'media_server_url' => env('MEDIA_SERVER_URL', 'wss://media.edusfera.by'),
    'media_server_internal_url' => env('MEDIA_SERVER_INTERNAL_URL', 'http://localhost:8088'),
    'jwt_secret' => env('CLASSROOM_JWT_SECRET'),
    'jwt_ttl' => (int) env('CLASSROOM_JWT_TTL', 3600),
    'jitsi_domain' => env('JITSI_DOMAIN', 'meet.jit.si'),

    // M2: Separate secret for internal S2S endpoints (whiteboard, etc.).
    // MUST differ from jwt_secret — used for HMAC auth, not JWT signing.
    'internal_secret' => env('CLASSROOM_INTERNAL_SECRET', env('CLASSROOM_JWT_SECRET')),

    // Требование 11.2, 11.8: алгоритм выпуска classroom-токена.
    // RS256 — новый асимметричный issuer; HS256-fallback живёт в
    // ClassroomService::generateMediaToken() для переходного периода.
    'token_algorithm' => env('CLASSROOM_TOKEN_ALG', 'RS256'),

    // Требование 11.2: пути к RSA keypair для подписи/проверки classroom-JWT.
    // Это ОТДЕЛЬНАЯ от Passport keypair (у classroom собственный kid).
    // Сгенерировать:
    //   openssl genrsa -out storage/classroom-private.key 4096
    //   openssl rsa -in storage/classroom-private.key -pubout -out storage/classroom-public.key
    'jwt_private_key_path' => env('CLASSROOM_JWT_PRIVATE_KEY_PATH', storage_path('classroom-private.key')),
    'jwt_public_key_path' => env('CLASSROOM_JWT_PUBLIC_KEY_PATH', storage_path('classroom-public.key')),

    // Требование 11.3: exp = min(lesson.end_time + grace, now() + max_ttl).
    'jwt_grace' => (int) env('CLASSROOM_JWT_GRACE', 600),
    'jwt_max_ttl' => (int) env('CLASSROOM_JWT_MAX_TTL', 7200),

    // Требование 11.4: claims iss/aud выпускаемого токена.
    'jwt_issuer' => env('CLASSROOM_JWT_ISS', config('app.url')),
    'jwt_audience' => env('CLASSROOM_JWT_AUD', 'classroom-service'),
    'max_file_size_mb' => (int) env('CLASSROOM_MAX_FILE_MB', 50),
    'allowed_file_types' => ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'gif'],
    'max_room_size' => (int) env('CLASSROOM_MAX_ROOM_SIZE', 5),
    'ice_servers' => [
        ['urls' => 'stun:stun.l.google.com:19302'],
        ['urls' => 'stun:stun1.l.google.com:19302'],
        ['urls' => 'stun:stun2.l.google.com:19302'],
        ['urls' => 'stun:stun.cloudflare.com:3478'],
        ['urls' => 'stun:global.stun.twilio.com:3478'],
    ],
    'turn' => [
        'url' => env('TURN_SERVER_URL'),
        'username' => env('TURN_SERVER_USERNAME', 'edusfera'),
        'credential' => env('TURN_SERVER_CREDENTIAL', 'change-me-strong-password'),
        'realm' => env('TURN_SERVER_REALM', 'edusfera.by'),
    ],
    'metered' => [
        'app_name' => env('METERED_APP_NAME'),
        'api_key' => env('METERED_API_KEY'),
    ],
    'workspace_internal_url' => env('WORKSPACE_INTERNAL_URL', 'http://localhost:8083'),
    'workspace_internal_secret' => env('WORKSPACE_INTERNAL_SECRET'),
];
