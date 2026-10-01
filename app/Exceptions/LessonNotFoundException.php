<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\Classroom\ClassroomTokenService;
use RuntimeException;

/**
 * Доменное исключение: запрошенный урок не найден.
 *
 * Бросается из application-сервиса {@see ClassroomTokenService},
 * чтобы транспортный слой (контроллер) мог замапить его в 404 в стандартном формате
 * без зависимости от деталей Eloquent.
 */
class LessonNotFoundException extends RuntimeException {}
