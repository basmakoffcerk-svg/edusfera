<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует именованный rate-limiter `api.v1`, применяемый к группе
 * публичного API через middleware `throttle:api.v1` в `routes/api.php`.
 *
 * Покрывает требование 17 спеки microservices-foundation:
 *  - 17.1: rate-limit `api.v1` определён в RouteServiceProvider.
 *  - 17.2: 60 запросов/мин на user_id для Sanctum-пользователя.
 *  - 17.3: 600 запросов/мин на client_id для service-токена (Passport
 *          client_credentials). Passport устанавливается в задаче 16, поэтому
 *          здесь оставлена точка расширения с graceful fallback на IP.
 *  - 17.4: при превышении лимита — 429 c заголовком `Retry-After`.
 *  - 17.5: заголовки `X-RateLimit-Limit` и `X-RateLimit-Remaining` в каждом
 *          успешном ответе (их добавляет Illuminate ThrottleRequests при
 *          использовании именованного limiter'а).
 */
final class RouteServiceProvider extends ServiceProvider
{
    public const API_V1_LIMITER = 'api.v1';

    public const WEB_AUTH_LIMITER = 'web.auth';

    public const CHECKOUT_PAY_LIMITER = 'checkout.pay';

    public function boot(): void
    {
        $this->configureApiV1RateLimiter();
        $this->configureWebAuthRateLimiter();
        $this->configureCheckoutPayRateLimiter();
    }

    /**
     * Определяет лимитер `api.v1`:
     *   1. Sanctum-пользователь  → 60/min  на user_id
     *   2. Passport client (S2S) → 600/min на client_id   (future, см. resolveServiceClientId)
     *   3. иначе                 → fallback по IP
     *
     * При превышении возвращается JSON-конверт ошибки `{"error":{"code":"rate_limited",...}}`
     * с заголовками Retry-After / X-RateLimit-*, которые формирует ThrottleRequests.
     */
    private function configureApiV1RateLimiter(): void
    {
        RateLimiter::for(self::API_V1_LIMITER, function (Request $request): Limit {
            ['maxAttempts' => $maxAttempts, 'key' => $key] = $this->resolveLimit($request);

            return Limit::perMinute($maxAttempts)
                ->by($key)
                ->response(function (Request $request, array $headers): JsonResponse {
                    return $this->tooManyRequestsResponse($headers);
                });
        });
    }

    /**
     * Выбирает лимит и ключ группировки в зависимости от субъекта запроса.
     *
     * @return array{maxAttempts:int, key:string}
     */
    private function resolveLimit(Request $request): array
    {
        /** @var array{user:int, client:int, ip:int} $throttle */
        $throttle = config('api.throttle', ['user' => 60, 'client' => 600, 'ip' => 60]);

        // 1. Аутентифицированный Sanctum-пользователь → per user_id (требование 17.2).
        //    Пытаемся через дефолтный guard (его выставляет будущий ApiAuthenticate /
        //    тестовый Sanctum::actingAs), затем явно через guard `sanctum`.
        $user = $this->resolveAuthenticatedUser($request);
        if ($user !== null) {
            return [
                'maxAttempts' => (int) $throttle['user'],
                'key' => 'user:'.$user->getAuthIdentifier(),
            ];
        }

        // 2. Service-токен (Passport client_credentials) → per client_id (требование 17.3).
        //    Passport ещё не установлен (задача 16); когда появится — client_id будет
        //    проставляться ApiAuthenticate в request-атрибут `oauth_client_id`.
        $clientId = $this->resolveServiceClientId($request);
        if ($clientId !== null) {
            return [
                'maxAttempts' => (int) $throttle['client'],
                'key' => 'client:'.$clientId,
            ];
        }

        // 3. Неаутентифицированный запрос → fallback по IP.
        return [
            'maxAttempts' => (int) $throttle['ip'],
            'key' => 'ip:'.(string) $request->ip(),
        ];
    }

    /**
     * Резолвит пользователя best-effort: сначала дефолтный guard, затем `sanctum`.
     * Любые ошибки резолвинга трактуются как «пользователь не аутентифицирован».
     */
    private function resolveAuthenticatedUser(Request $request): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        try {
            $user = $request->user();
            if ($user !== null) {
                return $user;
            }

            if (in_array('sanctum', array_keys((array) config('auth.guards', [])), true)) {
                return $request->user('sanctum');
            }
        } catch (\Throwable) {
            // Guard может быть не сконфигурирован в некоторых контекстах — это не ошибка.
        }

        return null;
    }

    /**
     * Точка расширения для Passport client_credentials (задача 16).
     *
     * Сейчас S2S-аутентификация ещё не подключена, поэтому client_id берётся
     * best-effort из request-атрибута / контейнера `oauth_client_id` (его будет
     * проставлять ApiAuthenticate). Если значения нет — возвращаем null, и
     * лимитер деградирует к IP-fallback.
     */
    private function resolveServiceClientId(Request $request): ?string
    {
        $fromRequest = $request->attributes->get('oauth_client_id');
        if (is_string($fromRequest) && $fromRequest !== '') {
            return $fromRequest;
        }

        if ($this->app->bound('oauth_client_id')) {
            $fromContainer = $this->app->make('oauth_client_id');
            if (is_string($fromContainer) && $fromContainer !== '') {
                return $fromContainer;
            }
        }

        return null;
    }

    private function configureCheckoutPayRateLimiter(): void
    {
        RateLimiter::for(self::CHECKOUT_PAY_LIMITER, function (Request $request): Limit {
            $limit = $this->app->environment('local', 'testing') ? 100 : 5;

            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });
    }

    private function configureWebAuthRateLimiter(): void
    {
        RateLimiter::for(self::WEB_AUTH_LIMITER, function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers): JsonResponse {
                    return $this->tooManyRequestsResponse($headers);
                });
        });
    }

    /**
     * 429-ответ в едином формате конверта ошибки `{"error":{...}}`.
     * Заголовки Retry-After / X-RateLimit-* передаются ThrottleRequests и
     * прикрепляются к ответу (требование 17.4).
     *
     * @param  array<string, int|string>  $headers
     */
    private function tooManyRequestsResponse(array $headers): JsonResponse
    {
        $requestId = $this->app->bound('request_id')
            ? (string) $this->app->make('request_id')
            : null;

        return response()->json([
            'error' => [
                'code' => 'rate_limited',
                'message' => 'Too Many Requests.',
                'request_id' => $requestId,
            ],
        ], 429)->withHeaders($headers);
    }
}
