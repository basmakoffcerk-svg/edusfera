<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Http\Api\V1\Resources\Dto\SubjectDto;
use App\Http\Middleware\AssignRequestId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Smoke-эндпоинт `GET /api/v1/me` (требование 1.5).
 *
 * Возвращает информацию об аутентифицированном субъекте: `user_id`, `role`,
 * `scopes` (abilities токена) и `request_id`. Ответ собирается через readonly
 * DTO {@see SubjectDto}, без прямой сериализации Eloquent-модели (требование 6.6).
 *
 * Аутентификация выполняется middleware `auth:sanctum` (требования 2.3, 2.4):
 * без валидного токена запрос до контроллера не доходит и возвращается 401.
 */
final class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $dto = new SubjectDto(
            userId: (int) $user->getAuthIdentifier(),
            role: $this->resolveRole($user),
            scopes: $this->resolveScopes($request),
            requestId: $this->resolveRequestId($request),
        );

        return response()->json($dto->toArray());
    }

    /**
     * Роль субъекта читается из явного поля модели (admin/tutor/student/parent).
     */
    private function resolveRole(object $user): ?string
    {
        $role = $user->role ?? null;

        if ($role instanceof \UnitEnum) {
            return (string) $role->value;
        }

        return is_string($role) ? $role : null;
    }

    /**
     * Scope-ы (abilities) personal access token'а Sanctum. Если запрос выполнен
     * без активного токена (например, через сессионный guard), возвращаем [].
     *
     * @return list<string>
     */
    private function resolveScopes(Request $request): array
    {
        $user = $request->user();

        if ($user === null || ! method_exists($user, 'currentAccessToken')) {
            return [];
        }

        $token = $user->currentAccessToken();

        if ($token === null) {
            return [];
        }

        /** @var list<string> $abilities */
        $abilities = $token->abilities ?? [];

        return array_values(array_filter($abilities, 'is_string'));
    }

    /**
     * request_id берём из контейнера (его кладёт AssignRequestId middleware),
     * с graceful fallback на заголовок X-Request-Id.
     */
    private function resolveRequestId(Request $request): ?string
    {
        if (app()->bound(AssignRequestId::CONTAINER_KEY)) {
            $value = app(AssignRequestId::CONTAINER_KEY);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $header = $request->headers->get(AssignRequestId::HEADER);

        return is_string($header) && $header !== '' ? $header : null;
    }
}
