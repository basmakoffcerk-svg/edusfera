<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Защита эндпоинта `/metrics`.
 *
 * Способ защиты выбирается через `config('metrics.protection')`:
 *   scope      — требуется аутентифицированный токен со scope
 *                `internal:metrics:read` (логика проверки совпадает
 *                с {@see EnforceServiceScope}: атрибут запроса `oauth_scopes`
 *                для Passport client_credentials или `tokenCan()` для Sanctum);
 *   basic_auth — требуется HTTP Basic auth с парой user/password из конфига.
 *
 * При отсутствии валидных кредов возвращает 401.
 */
final class MetricsAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $protection = (string) config('metrics.protection', 'scope');

        $authorized = match ($protection) {
            'basic_auth' => $this->passesBasicAuth($request),
            default => $this->passesScope($request),
        };

        if (! $authorized) {
            return $this->unauthorizedResponse($protection);
        }

        return $next($request);
    }

    /**
     * Проверка scope `internal:metrics:read` в токене запроса.
     *
     * Повторяет подход EnforceServiceScope:
     *  1. Passport client_credentials — атрибут запроса `oauth_scopes`.
     *  2. Sanctum personal access token — `$user->tokenCan($scope)`.
     *  3. Passport fallback — `$user->token()?->can($scope)`.
     */
    private function passesScope(Request $request): bool
    {
        $required = (string) config('metrics.scope', 'internal:metrics:read');

        $oauthScopes = $request->attributes->get('oauth_scopes');
        if (is_array($oauthScopes)) {
            return in_array($required, $oauthScopes, strict: true);
        }

        $user = $request->user();
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'tokenCan')) {
            return (bool) $user->tokenCan($required);
        }

        if (method_exists($user, 'token') && $user->token() !== null) {
            return (bool) $user->token()->can($required);
        }

        return false;
    }

    /**
     * Проверка HTTP Basic auth против пары из `config('metrics.basic_auth')`.
     * Сравнение паролей — constant-time (hash_equals), чтобы не давать timing-сигнал.
     */
    private function passesBasicAuth(Request $request): bool
    {
        $expectedUser = (string) config('metrics.basic_auth.user', '');
        $expectedPassword = (string) config('metrics.basic_auth.password', '');

        // Если креды не сконфигурированы — запрещаем доступ (fail-closed).
        if ($expectedUser === '' || $expectedPassword === '') {
            return false;
        }

        [$user, $password] = $this->resolveBasicCredentials($request);

        return hash_equals($expectedUser, $user)
            && hash_equals($expectedPassword, $password);
    }

    /**
     * Достаёт пару (user, password) из Basic-auth. Сначала через нативные
     * аксессоры Symfony (PHP_AUTH_USER/PW), затем — парсингом заголовка
     * `Authorization: Basic <base64>` как fallback (актуально для test-клиента
     * и некоторых SAPI, где PHP_AUTH_* не заполняются автоматически).
     *
     * @return array{0:string, 1:string}
     */
    private function resolveBasicCredentials(Request $request): array
    {
        $user = (string) ($request->getUser() ?? '');
        $password = (string) ($request->getPassword() ?? '');

        if ($user !== '' || $password !== '') {
            return [$user, $password];
        }

        $header = (string) $request->headers->get('Authorization', '');
        if (stripos($header, 'Basic ') === 0) {
            $decoded = base64_decode(substr($header, 6), true);
            if (is_string($decoded) && str_contains($decoded, ':')) {
                [$user, $password] = explode(':', $decoded, 2);

                return [(string) $user, (string) $password];
            }
        }

        return ['', ''];
    }

    /**
     * Формирует 401-ответ. Для basic-auth добавляет заголовок WWW-Authenticate,
     * чтобы клиент мог предложить ввод кредов.
     */
    private function unauthorizedResponse(string $protection): Response
    {
        $headers = [];
        if ($protection === 'basic_auth') {
            $headers['WWW-Authenticate'] = 'Basic realm="metrics"';
        }

        return response()->json([
            'error' => [
                'code' => 'unauthenticated',
                'message' => 'Valid metrics credentials are required to access this resource.',
                'request_id' => $this->resolveRequestId(),
            ],
        ], Response::HTTP_UNAUTHORIZED, $headers);
    }

    private function resolveRequestId(): string
    {
        try {
            return (string) app('request_id');
        } catch (\Throwable) {
            return '';
        }
    }
}
