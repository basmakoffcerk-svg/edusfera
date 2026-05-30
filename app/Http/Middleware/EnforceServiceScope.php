<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки OAuth2 scope в токене запроса.
 *
 * Поддерживает два типа токенов:
 *  1. Sanctum personal access token — проверяется через `$user->tokenCan($scope)`.
 *  2. Passport client_credentials token — проверяется через атрибут запроса
 *     `oauth_scopes` (который Passport проставляет автоматически) или через
 *     `$user->token()->can($scope)` как fallback.
 *
 * Если пользователь не аутентифицирован — возвращает 403, так как scope
 * не может быть проверен без токена.
 *
 * Использование в роутах:
 *   Route::get('/resource', Handler::class)->middleware('scope:lessons:read');
 *
 * Покрывает требования 13.2, 13.3, 13.4 спеки microservices-foundation.
 */
final class EnforceServiceScope
{
    public function __invoke(Request $request, Closure $next, string $required): Response
    {
        if (! $this->hasScope($request, $required)) {
            return $this->insufficientScopeResponse($request);
        }

        return $next($request);
    }

    /**
     * Проверяет наличие требуемого scope в токене запроса.
     *
     * Порядок проверки:
     *  1. Если пользователь не аутентифицирован — false.
     *  2. Passport: атрибут запроса `oauth_scopes` (проставляется CheckClientCredentials).
     *  3. Sanctum: `$user->tokenCan($scope)` (abilities personal access token).
     *  4. Passport fallback: `$user->token()?->can($scope)`.
     */
    private function hasScope(Request $request, string $required): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        // Passport client_credentials: CheckClientCredentials middleware
        // проставляет атрибут `oauth_scopes` в request attributes.
        $oauthScopes = $request->attributes->get('oauth_scopes');
        if (is_array($oauthScopes)) {
            return in_array($required, $oauthScopes, strict: true);
        }

        // Sanctum personal access token: проверяем через abilities.
        if (method_exists($user, 'tokenCan')) {
            return $user->tokenCan($required);
        }

        // Passport fallback: проверяем через объект токена.
        if (method_exists($user, 'token') && $user->token() !== null) {
            return $user->token()->can($required);
        }

        return false;
    }

    /**
     * Формирует 403-ответ с кодом ошибки `insufficient_scope`.
     *
     * Формат: {"error": {"code": "insufficient_scope", "message": "...", "request_id": "..."}}
     */
    private function insufficientScopeResponse(Request $request): Response
    {
        $requestId = $this->resolveRequestId();

        return response()->json([
            'error' => [
                'code' => 'insufficient_scope',
                'message' => 'The token does not have the required scope to access this resource.',
                'request_id' => $requestId,
            ],
        ], Response::HTTP_FORBIDDEN);
    }

    /**
     * Получает request_id из контейнера (проставляется AssignRequestId middleware).
     * Graceful fallback — пустая строка, если middleware не был запущен.
     */
    private function resolveRequestId(): string
    {
        try {
            return (string) app('request_id');
        } catch (\Throwable) {
            return '';
        }
    }
}
