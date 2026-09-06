<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use App\Exceptions\LessonAccessDeniedException;
use App\Exceptions\LessonNotFoundException;
use App\Http\Middleware\AssignRequestId;
use App\Services\Classroom\ClassroomTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Эндпоинт `GET /api/v1/lessons/{id}/classroom-token` (требования 11.5, 11.6).
 *
 * Возвращает короткоживущий classroom-JWT вместе с room id и служебными URL.
 * Аутентификация — `auth:sanctum` (без валидного токена middleware вернёт 401).
 * Проверка прав доступа и резолв урока/пользователя делегированы
 * {@see ClassroomTokenService}, потому что архитектурный инвариант
 * (требование 14.2) запрещает API-слою импортировать `App\Models\*`.
 *
 * Этот контроллер НЕ ссылается на Eloquent-модели: он оперирует только
 * идентификатором урока из маршрута и id аутентифицированного пользователя,
 * а доменные исключения мапит в HTTP-конверт `{"error":{...}}`.
 */
final class ClassroomTokenController
{
    public function __construct(
        private readonly ClassroomTokenService $service,
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $userId = (int) $request->user()->getAuthIdentifier();

        try {
            $dto = $this->service->issueForUser($id, $userId);
        } catch (LessonNotFoundException) {
            // Требование 11.5: несуществующий урок → 404 в формате конверта.
            return $this->errorResponse(
                Response::HTTP_NOT_FOUND,
                'not_found',
                'Lesson not found.',
            );
        } catch (LessonAccessDeniedException) {
            // Требование 11.6: нет прав на урок → 403 без выпуска токена.
            return $this->errorResponse(
                Response::HTTP_FORBIDDEN,
                'forbidden',
                'You do not have access to this lesson.',
            );
        }

        return response()->json($dto->toArray());
    }

    /**
     * Единый конверт ошибки `{"error": {"code", "message", "request_id"}}`,
     * согласованный с остальными middleware API-слоя (требование 1.4).
     */
    private function errorResponse(int $status, string $code, string $message): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'request_id' => $this->resolveRequestId(),
            ],
        ], $status);
    }

    /**
     * request_id берём из контейнера (его кладёт AssignRequestId middleware),
     * с graceful fallback на пустую строку, если middleware не отработал.
     */
    private function resolveRequestId(): string
    {
        if (app()->bound(AssignRequestId::CONTAINER_KEY)) {
            $value = app(AssignRequestId::CONTAINER_KEY);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }
}
