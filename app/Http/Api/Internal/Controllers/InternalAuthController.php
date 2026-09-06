<?php

declare(strict_types=1);

namespace App\Http\Api\Internal\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Контроллер для верификации JWT и Sanctum токенов на уровне API Gateway.
 *
 * Используется в Nginx auth_request для проверки токена и извлечения
 * метаданных пользователя (ID, роль, комната класса) во внутренние заголовки.
 */
final class InternalAuthController
{
    /**
     * Верифицирует токен из Authorization заголовка и возвращает метаданные.
     */
    public function verify(Request $request): JsonResponse
    {
        $authorization = $request->header('Authorization');
        if (! $authorization || ! str_starts_with($authorization, 'Bearer ')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing or invalid Authorization header'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tokenString = substr($authorization, 7);
        if ($tokenString === false || $tokenString === '') {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Empty token'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 1. Попытка валидации как Classroom JWT (RS256)
        try {
            $publicKeyPath = (string) config('classroom.jwt_public_key_path');
            if ($publicKeyPath !== '' && is_readable($publicKeyPath)) {
                $publicKey = file_get_contents($publicKeyPath);
                if ($publicKey !== false && trim($publicKey) !== '') {
                    $decoded = JWT::decode($tokenString, new Key($publicKey, 'RS256'));

                    if ($decoded && isset($decoded->sub) && isset($decoded->role)) {
                        return response()->json(['status' => 'ok'])
                            ->header('X-User-Id', (string) $decoded->sub)
                            ->header('X-User-Role', (string) $decoded->role)
                            ->header('X-Classroom-Room-Id', (string) ($decoded->room ?? ''));
                    }
                }
            }
        } catch (\Throwable $e) {
            // Не является валидным Classroom JWT, пробуем другие форматы
        }

        // 2. Попытка валидации как Sanctum PAT (opaque token)
        try {
            $tokenModel = PersonalAccessToken::findToken($tokenString);
            if ($tokenModel && $tokenModel->tokenable instanceof User) {
                $expiration = config('sanctum.expiration');
                $isValid = (! $expiration || $tokenModel->created_at->addMinutes($expiration)->isFuture()) &&
                           (! $tokenModel->expires_at || $tokenModel->expires_at->isFuture());

                if ($isValid) {
                    $user = $tokenModel->tokenable;
                    return response()->json(['status' => 'ok'])
                        ->header('X-User-Id', (string) $user->id)
                        ->header('X-User-Role', $user->role instanceof \UnitEnum ? $user->role->value : (string) $user->role);
                }
            }
        } catch (\Throwable $e) {
            // Не является валидным Sanctum токеном, пробуем Passport
        }

        // 3. Попытка валидации как Passport OAuth2 Token (S2S / User JWT)
        try {
            $psrRequest = (new PsrHttpFactory())->createRequest($request);
            $resourceServer = app(ResourceServer::class);
            $psrRequest = $resourceServer->validateAuthenticatedRequest($psrRequest);

            $userId = $psrRequest->getAttribute('oauth_user_id');
            $clientId = $psrRequest->getAttribute('oauth_client_id');

            if ($userId && $userId !== $clientId) {
                $user = User::find($userId);
                if ($user instanceof User) {
                    return response()->json(['status' => 'ok'])
                        ->header('X-User-Id', (string) $user->id)
                        ->header('X-User-Role', $user->role instanceof \UnitEnum ? $user->role->value : (string) $user->role);
                }
            } elseif ($clientId) {
                return response()->json(['status' => 'ok'])
                    ->header('X-User-Id', 'client-' . $clientId)
                    ->header('X-User-Role', 'service');
            }
        } catch (\Throwable $e) {
            // Игнорируем и переходим к следующему типу валидации
        }

        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Invalid token signature or expired'
        ], Response::HTTP_UNAUTHORIZED);
    }
}
