<?php

namespace App\Providers;

use App\Models\Lesson;
use App\Models\User;
use App\Policies\LessonPolicy;
use App\Services\Payment\DisabledPaymentGateway;
use App\Services\Payment\MockPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Lesson::class, LessonPolicy::class);
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
