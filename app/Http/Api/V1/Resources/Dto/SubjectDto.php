<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Resources\Dto;

/**
 * Readonly DTO аутентифицированного субъекта для smoke-эндпоинта
 * `GET /api/v1/me` (требование 1.5).
 *
 * DTO строится из явных полей и НЕ сериализует Eloquent-модель напрямую
 * (требование 6.6): API-слой отдаёт ответы только через readonly-DTO в
 * `app/Http/Api/V1/Resources/Dto/*`.
 */
final readonly class SubjectDto
{
    /**
     * @param  list<string>  $scopes  abilities/scope-ы токена, под которым выполнен запрос
     */
    public function __construct(
        public int $userId,
        public ?string $role,
        public array $scopes,
        public ?string $requestId,
    ) {}

    /**
     * Представление DTO в виде ассоциативного массива для JSON-ответа.
     *
     * @return array{user_id: int, role: string|null, scopes: list<string>, request_id: string|null}
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'role' => $this->role,
            'scopes' => array_values($this->scopes),
            'request_id' => $this->requestId,
        ];
    }
}
