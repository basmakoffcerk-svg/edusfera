<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Contracts\Classroom\ClassroomTokenIssuer;
use App\Domain\Subscription\Services\SubscriptionFeatureGate;
use App\Exceptions\LessonAccessDeniedException;
use App\Exceptions\LessonNotFoundException;
use App\Http\Api\V1\Resources\Dto\ClassroomTokenDto;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Application-сервис, обслуживающий эндпоинт
 * `GET /api/v1/lessons/{id}/classroom-token` (требования 11.5, 11.6).
 *
 * ── Зачем отдельный сервис ────────────────────────────────────────────────
 * Архитектурный инвариант (требование 14.2, {@see \Tests\Architecture\LayerIsolationTest})
 * запрещает классам под `app/Http/Api/V1/*` ссылаться на `App\Models\*`. Поэтому
 * вся работа с Eloquent (резолв урока/пользователя, проверка доступа) вынесена
 * сюда, в `app/Services/*`, где доступ к моделям разрешён. Контроллер лишь
 * передаёт идентификаторы и мапит доменные исключения в HTTP-ответы.
 */
final class ClassroomTokenService
{
    public function __construct(
        private readonly ClassroomTokenIssuer $issuer,
        private readonly ?SubscriptionFeatureGate $featureGate = null,
    ) {}

    /**
     * Выпустить classroom-токен для пользователя в контексте урока.
     *
     * @throws LessonNotFoundException если урок с таким id не существует (→ 404)
     * @throws LessonAccessDeniedException если у пользователя нет доступа к уроку (→ 403)
     * @throws ValidationException если у репетитора не активна подписка (→ 422)
     */
    public function issueForUser(int $lessonId, int $userId): ClassroomTokenDto
    {
        $lesson = Lesson::find($lessonId);

        if ($lesson === null) {
            throw new LessonNotFoundException("Lesson {$lessonId} not found.");
        }

        $user = User::find($userId);

        if ($user === null || ! $this->canAccess($lesson, $user)) {
            // Требование 11.6: без права доступа токен не выпускается.
            throw new LessonAccessDeniedException(
                "User {$userId} is not allowed to access lesson {$lessonId}."
            );
        }

        if (in_array($lesson->status, [Lesson::STATUS_CANCELLED], true) || $lesson->payment_status === Lesson::PAYMENT_UNPAID) {
            throw new LessonAccessDeniedException(
                "Cannot enter classroom for cancelled or unpaid lesson {$lessonId}."
            );
        }

        $tutor = $lesson->tutor ?? User::find($lesson->tutor_id);
        $gate = $this->featureGate ?? app(SubscriptionFeatureGate::class);

        if ($tutor !== null && ! $gate->canAccessClassroom($tutor)) {
            $isStudent = ($user->id !== $tutor->id);
            $message = $isStudent
                ? 'Для входа в виртуальный класс необходимо продлить подписку на платформу Edusfera. Виртуальный класс преподавателя временно неактивен.'
                : 'Для входа в виртуальный класс необходимо продлить подписку на платформу Edusfera.';

            throw ValidationException::withMessages([
                'subscription' => $message,
            ]);
        }

        $token = $this->issuer->issue($lesson, $user);

        return new ClassroomTokenDto(
            token: $token,
            room: $this->resolveRoom($lesson),
            urls: $this->resolveUrls(),
        );
    }

    /**
     * Проверка доступа к уроку. Делегируется LessonPolicy::view через Gate, что
     * совпадает с логикой владения уроком (admin / tutor / student / parent).
     */
    private function canAccess(Lesson $lesson, User $user): bool
    {
        return $user->can('view', $lesson);
    }

    /**
     * room id определяется тем же способом, что и в issuer
     * ({@see \App\Domain\Classroom\RsaClassroomTokenIssuer}): из активной
     * classroom-сессии, либо детерминированный fallback `lesson-{id}`.
     */
    private function resolveRoom(Lesson $lesson): string
    {
        $roomId = $lesson->activeClassroom?->room_id;

        if (is_string($roomId) && $roomId !== '') {
            return $roomId;
        }

        return 'lesson-'.$lesson->id;
    }

    /**
     * Служебные URL для подключения фронтенда к виртуальному классу:
     * адрес media-сервера и список ICE-серверов (STUN/TURN).
     *
     * @return array{media_server: mixed, ice_servers: mixed}
     */
    private function resolveUrls(): array
    {
        return [
            'media_server' => config('classroom.media_server_url'),
            'ice_servers' => config('classroom.ice_servers'),
        ];
    }
}
