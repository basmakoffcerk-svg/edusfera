<?php

declare(strict_types=1);

namespace App\Support;

class SecuritySanitizer
{
    private const SENSITIVE_KEYS = [
        'password',
        'current_password',
        'password_confirmation',
        'secret',
        'secret_key',
        'api_password',
        'api_key',
        'token',
        'access_token',
        'refresh_token',
        'card_number',
        'pan',
        'cvv',
        'cvc',
        'authorization',
        'ws_signature',
    ];

    /**
     * Topic 15: Секреты в логах — маскирует чувствительные ключи в массивах перед логированием.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function maskSensitiveData(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            $lowerKey = mb_strtolower((string) $key);

            if (in_array($lowerKey, self::SENSITIVE_KEYS, true) || str_contains($lowerKey, 'password') || str_contains($lowerKey, 'secret')) {
                $masked[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                $masked[$key] = self::maskSensitiveData($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Topic 11: SSRF — проверяет безопасен ли URL (запрещает внутренние IP, localhost и не-HTTP схемы).
     */
    public static function isSafeUrl(string $url): bool
    {
        $parsed = parse_url($url);

        if (! is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
            return false;
        }

        $scheme = mb_strtolower((string) $parsed['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = (string) $parsed['host'];

        if (mb_strtolower($host) === 'localhost' || str_ends_with(mb_strtolower($host), '.local')) {
            return false;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        if ($ip === false || ($ip === $host && ! filter_var($host, FILTER_VALIDATE_IP))) {
            return false;
        }

        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
