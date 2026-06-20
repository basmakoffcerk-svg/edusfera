<?php

declare(strict_types=1);

namespace App\Http\Api\Internal\Controllers;

use App\Contracts\Lesson\LessonReader;
use App\Http\Middleware\AssignRequestId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Внутренний эндпоинт для чтения уроков микросервисами.
 *
 * Все данные возвращаются через контракт {@see LessonReader} → {@see LessonDto},
 * без прямого использования Eloquent-моделей (требование 14.2).
 *
 * Аутентификация — Passport client_credentials + scope `lessons:read`.
 */
final class InternalLessonController
{
    public function __construct(
        private readonly LessonReader $reader,
    ) {}

    /**
     * GET /api/internal/v1/lessons/{id}
     *
     * Возвращает урок по id. 404 если не найден.
     */
    public function show(int $id): JsonResponse
    {
        $dto = $this->reader->find($id);

        if ($dto === null) {
            return $this->errorResponse(
                Response::HTTP_NOT_FOUND,
                'not_found',
                'Lesson not found.',
            );
        }

        return response()->json(['data' => $dto->toArray()]);
    }

    /**
     * GET /api/internal/v1/lessons?user_id=&status=
     *
     * Список уроков для пользователя. Параметр `user_id` обязателен.
     * Опциональная фильтрация по `status`.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->integer('user_id');

        if ($userId <= 0) {
            return $this->errorResponse(
                Response::HTTP_BAD_REQUEST,
                'validation_error',
                'The user_id query parameter is required and must be a positive integer.',
            );
        }

        $lessons = $this->reader->forUser($userId);

        // Опциональная фильтрация по status.
        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $lessons = array_values(array_filter(
                $lessons,
                static fn ($dto) => $dto->status === $status,
            ));
        }

        return response()->json([
            'data' => array_map(
                static fn ($dto) => $dto->toArray(),
                $lessons,
            ),
            'meta' => ['total' => count($lessons)],
        ]);
    }

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

    private function resolveRequestId(): string
    {
        try {
            return (string) app(AssignRequestId::CONTAINER_KEY);
        } catch (\Throwable) {
            return '';
        }
    }
}
