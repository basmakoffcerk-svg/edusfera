<?php

declare(strict_types=1);

return [
    'media_server_url' => env('MEDIA_SERVER_URL', 'wss://media.edusfera.by'),
    'media_server_internal_url' => env('MEDIA_SERVER_INTERNAL_URL', 'http://localhost:8088'),
    'jwt_secret' => env('CLASSROOM_JWT_SECRET'),
    'jwt_ttl' => (int) env('CLASSROOM_JWT_TTL', 3600),
    'max_file_size_mb' => (int) env('CLASSROOM_MAX_FILE_MB', 50),
    'allowed_file_types' => ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'gif'],
    'max_room_size' => (int) env('CLASSROOM_MAX_ROOM_SIZE', 5),
    'ice_servers' => [
        ['urls' => env('STUN_SERVER', 'stun:stun.l.google.com:19302')],
    ],
    'turn' => [
        'url' => env('TURN_SERVER_URL'),
        'username' => env('TURN_SERVER_USERNAME'),
        'credential' => env('TURN_SERVER_CREDENTIAL'),
    ],
];
