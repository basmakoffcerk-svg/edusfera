<?php

declare(strict_types=1);

namespace App\Domain\Classroom;

use App\Contracts\Classroom\ClassroomTokenIssuer;
use App\Models\Lesson;
use App\Models\User;
use Firebase\JWT\JWT;
use RuntimeException;

/**
 * Требование 11.1–11.4: выпуск classroom-JWT, подписанного RS256.
 *
 * Приватный ключ читается из файла, путь к которому задан в
 * `config('classroom.jwt_private_key_path')` (требование 11.2). Claim `exp`
 * вычисляется как `min(lesson.end_time + grace, now() + max_ttl)`
 * (требование 11.3), что гарантирует невозможность доступа к комнате после
 * завершения урока. Публичный ключ публикуется через JWKS (задача 23) — здесь
 * только issuer.
 */
class RsaClassroomTokenIssuer implements ClassroomTokenIssuer
{
    /**
     * Алгоритм подписи. Асимметричный RS256 позволяет classroom-сервису
     * валидировать токен по публичному ключу без shared secret.
     */
    private const ALGORITHM = 'RS256';

    public function issue(Lesson $lesson, User $user): string
    {
        $privateKey = $this->loadPrivateKey();

        $issuedAt = time();
        $role = $this->resolveRole($lesson, $user);

        $claims = [
            'iss' => (string) config('classroom.jwt_issuer'),
            'aud' => (string) config('classroom.jwt_audience'),
            'sub' => $user->id,
            'room' => $this->resolveRoom($lesson),
            'role' => $role,
            'capabilities' => $this->resolveCapabilities($role),
            'lesson_id' => $lesson->id,
            'iat' => $issuedAt,
            'exp' => $this->resolveExpiry($lesson, $issuedAt),
        ];

        return JWT::encode($claims, $privateKey, self::ALGORITHM, $this->resolveKeyId($privateKey));
    }

    /**
     * Требование 11.3: exp = min(lesson.end_time + grace, now() + max_ttl).
     *
     * Оба ограничения применяются одновременно: токен не переживёт ни конец
     * урока + grace, ни жёсткий потолок max_ttl от момента выпуска.
     */
    private function resolveExpiry(Lesson $lesson, int $issuedAt): int
    {
        $grace = (int) config('classroom.jwt_grace', 600);
        $maxTtl = (int) config('classroom.jwt_max_ttl', 7200);

        $endTimestamp = $lesson->end_time !== null
            ? $lesson->end_time->getTimestamp()
            : $issuedAt;

        $endPlusGrace = $endTimestamp + $grace;
        $maxTtlBound = $issuedAt + $maxTtl;

        return min($endPlusGrace, $maxTtlBound);
    }

    /**
     * Требование 11.4: role ∈ {tutor, student}. Тьютор определяется по
     * совпадению user.id с lesson.tutor_id, остальные считаются студентами.
     */
    private function resolveRole(Lesson $lesson, User $user): string
    {
        return $user->id === $lesson->tutor_id ? 'tutor' : 'student';
    }

    /**
     * Room id берём из активной classroom-сессии урока; если сессии ещё нет —
     * детерминированный fallback `lesson-{id}`, чтобы токен оставался валидным
     * до создания комнаты.
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
     * Требование 11.4: capabilities — массив возможностей участника.
     * Тьютор получает полный набор, студент — без управления экраном.
     */
    private function resolveCapabilities(string $role): array
    {
        return $role === 'tutor'
            ? ['whiteboard', 'chat', 'screen']
            : ['whiteboard', 'chat'];
    }

    private function loadPrivateKey(): string
    {
        $path = (string) config('classroom.jwt_private_key_path');

        if ($path === '' || ! is_readable($path)) {
            throw new RuntimeException(
                'Classroom JWT private key is not configured or not readable: '.$path
            );
        }

        $key = file_get_contents($path);

        if ($key === false || trim($key) === '') {
            throw new RuntimeException('Classroom JWT private key file is empty: '.$path);
        }

        return $key;
    }

    /**
     * Требование 11.7 / 3.7: kid идентифицирует classroom-ключ в JWKS и
     * поддерживает ротацию. Вычисляется как усечённый sha256 публичного ключа,
     * выведенного из приватного, чтобы быть стабильным для одной keypair и
     * отличаться от Passport-ключа.
     */
    private function resolveKeyId(string $privateKey): string
    {
        $publicKeyPath = (string) config('classroom.jwt_public_key_path');

        if ($publicKeyPath !== '' && is_readable($publicKeyPath)) {
            $publicKey = file_get_contents($publicKeyPath);

            if ($publicKey !== false && trim($publicKey) !== '') {
                return 'classroom-'.substr(hash('sha256', $publicKey), 0, 16);
            }
        }

        // Fallback: производный публичный ключ из приватного.
        $resource = openssl_pkey_get_private($privateKey);

        if ($resource !== false) {
            $details = openssl_pkey_get_details($resource);

            if (is_array($details) && isset($details['key'])) {
                return 'classroom-'.substr(hash('sha256', (string) $details['key']), 0, 16);
            }
        }

        return 'classroom-current';
    }
}
