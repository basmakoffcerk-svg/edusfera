<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Доменное исключение: запрошенный урок не найден.
 *
 * Бросается из application-сервиса {@see \App\Services\Classroom\ClassroomTokenService},
 * чтобы транспортный слой (контроллер) мог замапить его в 404 в стандартном формате
 * без зависимости от деталей Eloquent.
 */
class LessonNotFoundException extends RuntimeException {}
