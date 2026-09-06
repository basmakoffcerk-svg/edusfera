<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources\Dto;

/**
 * Readonly DTO ответа эндпоинта `GET /api/v1/lessons/{id}/classroom-token`
 * (требование 11.5).
 *
 * Содержит выпущенный classroom-JWT (`token`), идентификатор комнаты (`room`)
 * и набор служебных URL-ов (`urls`: media-сервер + ICE-серверы). Ответ API
 * собирается только через readonly-DTO, без прямой сериализации Eloquent
 * (требование 6.6).
 */
final readonly class ClassroomTokenDto
{
    /**
     * @param  array<string, mixed>  $urls  служебные URL для подключения к классу
     */
    public function __construct(
        public string $token,
        public string $room,
        public array $urls,
    ) {}

    /**
     * Представление DTO в виде ассоциативного массива для JSON-ответа.
     *
     * @return array{token: string, room: string, urls: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'room' => $this->room,
            'urls' => $this->urls,
        ];
    }
}
