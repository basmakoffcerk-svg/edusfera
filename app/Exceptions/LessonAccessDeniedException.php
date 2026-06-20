<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Доменное исключение: у пользователя нет доступа к уроку.
 *
 * Бросается из application-сервиса {@see \App\Services\Classroom\ClassroomTokenService}
 * при отказе LessonPolicy::view, чтобы контроллер замапил его в 403 Forbidden
 * без выпуска токена (требование 11.6).
 */
class LessonAccessDeniedException extends RuntimeException {}
