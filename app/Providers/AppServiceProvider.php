<?php

namespace App\Providers;

use App\Contracts\Events\EventBusInterface;
use App\Contracts\Lesson\LessonBooker;
use App\Contracts\Lesson\LessonReader;
use App\Integrations\EventBus\NullEventBus;
use App\Integrations\EventBus\RedisStreamsEventBus;
use App\Models\Lesson;
use App\Models\User;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Lesson::class, LessonPolicy::class);

        // Требование 3.1, 3.3: включить grant client_credentials и ограничить TTL токенов 30 мин.
        // В Passport v13 client_credentials grant включён по умолчанию.
        // Устанавливаем TTL для всех токенов и отдельно для client_credentials.
        Passport::tokensExpireIn(now()->addMinutes(30));
        Passport::clientCredentialsTokensExpireIn(now()->addMinutes(30));

        // Требование 13.1: регистрируем каталог допустимых scope-ов.
        // Passport::tokensCan() принимает массив [scope => description].
        Passport::tokensCan(config('oauth.scopes', []));

        $this->syncTechnicalAdminAccount();
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
