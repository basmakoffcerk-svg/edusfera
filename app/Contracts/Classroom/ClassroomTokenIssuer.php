<?php

declare(strict_types=1);

namespace App\Contracts\Classroom;

use App\Models\Lesson;
use App\Models\User;

/**
 * Требование 11.1: контракт выдачи короткоживущего classroom-JWT.
 *
 * Реализация подписывает токен RS256 (требование 11.2), привязывает `exp`
 * к концу урока + grace (требование 11.3) и включает обязательный набор
 * claim-ов sub/room/role/capabilities/lesson_id/iss/aud (требование 11.4).
 */
interface ClassroomTokenIssuer
{
    /**
     * Выпустить classroom-JWT для пользователя в контексте конкретного урока.
     */
    public function issue(Lesson $lesson, User $user): string;
}
