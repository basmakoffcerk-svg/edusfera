<?php

declare(strict_types=1);

namespace App\Http\Api\V1\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA\PublicKey;
use RuntimeException;

/**
 * JWKS endpoint — публикует публичные ключи RS256 в формате JWK Set (RFC 7517).
 *
 * Микросервисы используют этот эндпоинт для самостоятельной валидации
 * access-токенов и classroom-JWT без обращения к ядру.
 *
 * Поддерживает ротацию ключей без даунтайма (требование 3.7):
 * если задан PASSPORT_PREVIOUS_PUBLIC_KEY_PATH, оба ключа публикуются
 * одновременно с разными kid.
 *
 * Requirements: 3.4, 3.7, 11.7
 */
final class JwksController extends Controller
{
    /**
     * GET /api/v1/.well-known/jwks.json
     *
     * Возвращает JWK Set с текущим (и предыдущим, если задан) публичным ключом.
     */
    public function __invoke(): JsonResponse
    {
        $keys = [];

        // Текущий публичный ключ
        $currentKeyPath = config('passport.public_key_path');
        if ($currentKeyPath && file_exists((string) $currentKeyPath)) {
            $keys[] = $this->buildJwk((string) $currentKeyPath, 'current');
        } elseif (config('passport.public_key')) {
            // Ключ задан как PEM-строка в ENV
            $keys[] = $this->buildJwkFromPem((string) config('passport.public_key'), 'current');
        }

        // Предыдущий публичный ключ (для ротации без даунтайма)
        $previousKeyPath = config('passport.previous_public_key_path');
        if ($previousKeyPath && file_exists((string) $previousKeyPath)) {
            $keys[] = $this->buildJwk((string) $previousKeyPath, 'previous');
        }

        // Classroom публичный ключ (требование 11.7): отдельная RSA keypair с
        // собственным kid, чтобы classroom-сервис мог валидировать classroom-JWT
        // по JWKS без shared secret. Публикуется только если файл ключа доступен —
        // в окружениях без classroom-ключа поведение JWKS не меняется.
        $classroomKey = $this->buildClassroomJwk();
        if ($classroomKey !== null) {
            $keys[] = $classroomKey;
        }

        return response()->json(['keys' => $keys]);
    }

    /**
     * Строит JWK для classroom-публичного ключа (требование 11.7).
     *
     * KID вычисляется ИМЕННО так же, как в {@see \App\Domain\Classroom\RsaClassroomTokenIssuer}:
     * `'classroom-'.substr(sha256(publicKeyPem), 0, 16)`. Это критично — иначе
     * classroom-сервис не сопоставит ключ из JWKS с `kid` в header выпущенного
     * токена и не сможет проверить подпись.
     *
     * Возвращает null, если classroom-публичный ключ не сконфигурирован или
     * недоступен (тогда classroom-ключ просто не попадает в JWK Set).
     */
    private function buildClassroomJwk(): ?array
    {
        $publicKeyPath = config('classroom.jwt_public_key_path');

        if (! $publicKeyPath || ! is_readable((string) $publicKeyPath)) {
            return null;
        }

        $pem = file_get_contents((string) $publicKeyPath);

        if ($pem === false || trim($pem) === '') {
            return null;
        }

        // KID согласован с RsaClassroomTokenIssuer::resolveKeyId().
        $kid = 'classroom-'.substr(hash('sha256', $pem), 0, 16);

        return $this->buildJwkFromPem($pem, 'classroom', $kid);
    }

    /**
     * Строит JWK из файла с публичным ключом.
     */
    private function buildJwk(string $path, string $kidSuffix): array
    {
        $pem = file_get_contents($path);

        if ($pem === false) {
            throw new RuntimeException("Cannot read public key file: {$path}");
        }

        return $this->buildJwkFromPem($pem, $kidSuffix);
    }

    /**
     * Строит JWK из PEM-строки публичного ключа.
     *
     * Поля JWK (RFC 7517 + RFC 7518):
     *   kty — тип ключа (RSA)
     *   use — назначение (sig — для подписи)
     *   alg — алгоритм (RS256)
     *   kid — идентификатор ключа (sha256 от n, усечённый до 16 символов)
     *   n   — модуль RSA в base64url
     *   e   — публичная экспонента в base64url
     *
     * @param  string|null  $kidOverride  Явный kid (для classroom-ключа он должен
     *                                    совпадать с тем, что issuer кладёт в header
     *                                    токена). Если null — kid вычисляется из
     *                                    модуля + суффикса (схема Passport-ключей).
     */
    private function buildJwkFromPem(string $pem, string $kidSuffix, ?string $kidOverride = null): array
    {
        /** @var PublicKey $publicKey */
        $publicKey = PublicKeyLoader::load($pem);

        if (! $publicKey instanceof PublicKey) {
            throw new RuntimeException('Loaded key is not an RSA public key.');
        }

        // Получаем JWK через phpseclib3 и декодируем
        $jwkJson = $publicKey->toString('JWK');
        $jwkData = json_decode($jwkJson, true);

        // phpseclib3 возвращает {"keys": [...]} — берём первый ключ
        $jwk = $jwkData['keys'][0] ?? $jwkData;

        // Для Passport-ключей kid генерируется из хэша модуля + суффикса (уникальность
        // при ротации). Для classroom-ключа kid задаётся явно, чтобы совпасть с issuer.
        $kid = $kidOverride ?? substr(hash('sha256', ($jwk['n'] ?? '').$kidSuffix), 0, 16);

        return [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $kid,
            'n' => $jwk['n'],
            'e' => $jwk['e'],
        ];
    }
}
