<?php

namespace App\Providers;

use App\Contracts\Classroom\ClassroomTokenIssuer;
use App\Contracts\Events\EventBusInterface;
use App\Contracts\Integrations\AiAssistantClient;
use App\Contracts\Lesson\LessonBooker;
use App\Contracts\Lesson\LessonReader;
use App\Domain\Classroom\RsaClassroomTokenIssuer;
use App\Integrations\AI\NullAiAssistantClient;
use App\Integrations\EventBus\NullEventBus;
use App\Integrations\EventBus\RedisStreamsEventBus;
use App\Models\ClassroomFile;
use App\Models\ClassroomNote;
use App\Models\Lesson;
use App\Models\User;
use App\Policies\ClassroomFilePolicy;
use App\Policies\ClassroomNotePolicy;
use App\Policies\LessonPolicy;
use App\Services\Lesson\EloquentLessonBooker;
use App\Services\Lesson\EloquentLessonReader;
use App\Services\Payment\DisabledPaymentGateway;
use App\Services\Payment\MockPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laravel\Passport\Passport;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return match (config('payments.gateway', 'mock')) {
                'bepaid' => new \App\Services\Payment\BePaidPaymentGateway,
                'mock' => new MockPaymentGateway,
                'disabled' => new DisabledPaymentGateway,
                default => throw new InvalidArgumentException('Unknown payment gateway ['.config('payments.gateway').'].'),
            };
        });

        // Требование 9.4: биндинг EventBusInterface на основе config('events.bus.driver').
        $this->app->bind(EventBusInterface::class, function () {
            return match (config('events.bus.driver', 'null')) {
                'redis_streams' => new RedisStreamsEventBus,
                'log' => new NullEventBus(logEnabled: true),
                default => new NullEventBus,
            };
        });

        // Требование 6.2, 6.3: контракты контекста Lesson разрывают связь UI ↔ Eloquent.
        $this->app->bind(LessonReader::class, EloquentLessonReader::class);
        $this->app->bind(LessonBooker::class, EloquentLessonBooker::class);

        // Требование 15.1: контракт клиента AI-сервиса. В фундаменте AI-сервиса
        // ещё нет — биндим заглушку, возвращающую пустой набор обработанных
        // lesson_id. Реальный HTTP-клиент заменит биндинг в feature-spec'е AI.
        $this->app->bind(AiAssistantClient::class, NullAiAssistantClient::class);

        // Требование 11.1, 11.8: classroom-token-issuer. Feature-флаг
        // classroom.token_algorithm управляет алгоритмом: RS256 (по умолчанию)
        // → RsaClassroomTokenIssuer. HS256-fallback остаётся в
        // ClassroomService::generateMediaToken() для переходного периода.
        $this->app->bind(ClassroomTokenIssuer::class, function () {
            return match (config('classroom.token_algorithm', 'RS256')) {
                'RS256' => new RsaClassroomTokenIssuer,
                default => new RsaClassroomTokenIssuer,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(ClassroomFile::class, ClassroomFilePolicy::class);
        Gate::policy(ClassroomNote::class, ClassroomNotePolicy::class);

        // Требование 3.1, 3.3: включить grant client_credentials и ограничить TTL токенов 30 мин.
        // В Passport v13 client_credentials grant включён по умолчанию.
        // Устанавливаем TTL для всех токенов и отдельно для client_credentials.
        Passport::tokensExpireIn(now()->addMinutes(30));
        Passport::clientCredentialsTokensExpireIn(now()->addMinutes(30));

        // Требование 13.1: регистрируем каталог допустимых scope-ов.
        // Passport::tokensCan() принимает массив [scope => description].
        Passport::tokensCan(config('oauth.scopes', []));

        $this->syncTechnicalAdminAccount();
        $this->enforceProductionSecurity();
    }

    /**
     * Enforce security invariants that must never reach production.
     */
    private function enforceProductionSecurity(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        // M6: APP_DEBUG must be false in production — leaky error pages expose stack traces.
        if (config('app.debug')) {
            abort(500, 'APP_DEBUG must be disabled in production.');
        }

        // C2: SESSION_SECURE_COOKIE must be true in production — prevents cookie interception over HTTP.
        if (config('session.secure') !== true) {
            abort(500, 'SESSION_SECURE_COOKIE must be true in production.');
        }
    }

    private function syncTechnicalAdminAccount(): void
    {
        if (! (bool) config('site_admin.sync_enabled', false)) {
            return;
        }

        $email = mb_strtolower(trim((string) config('site_admin.email', '')));
        $password = (string) config('site_admin.password', '');
        $name = trim((string) config('site_admin.name', 'Технический администратор'));

        if ($email === '' || $password === '') {
            return;
        }

        // M8: reject trivially weak passwords in production.
        if (app()->isProduction() && strlen($password) < 16) {
            report(new \InvalidArgumentException('SITE_ADMIN_PASSWORD must be at least 16 characters in production.'));

            return;
        }

        try {
            if (! Schema::hasTable('users')) {
                return;
            }

            $admin = User::query()->firstOrNew(['email' => $email]);

            $admin->name = $name !== '' ? $name : 'Технический администратор';
            $admin->role = 'admin';
            $admin->is_verified = true;

            if (! $admin->exists || ! Hash::check($password, (string) $admin->password)) {
                $admin->password = $password;
            }

            if (! $admin->exists || ! $admin->offer_accepted_at) {
                $admin->offer_accepted_at = now();
            }

            $admin->save();
        } catch (Throwable) {
            // keep boot resilient for commands that run before DB is ready
        }
    }
}
