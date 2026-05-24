<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Support\Facades\Hash;

class Login extends \Filament\Pages\Auth\Login
{
    protected static string $view = 'filament.admin.pages.auth.login';

    public function mount(): void
    {
        parent::mount();

        $redirectTo = request()->query('redirect_to');

        if (is_string($redirectTo) && $this->isSafeRedirect($redirectTo)) {
            session(['auth.redirect_to' => $redirectTo]);
        }
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email или телефон')
            ->placeholder('name@example.com или +375XXXXXXXXX')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes([
                'tabindex' => 1,
                'inputmode' => 'text',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->autocomplete('current-password')
            ->extraInputAttributes([
                'tabindex' => 2,
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $login = trim((string) ($data['email'] ?? ''));

        $user = $this->resolveUserFromLogin($login);

        if (! $user || ! Hash::check((string) ($data['password'] ?? ''), (string) $user->password)) {
            $this->throwFailureValidationException();
        }

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        Filament::auth()->login($user, (bool) ($data['remember'] ?? false));
        session()->regenerate();

        // Automatically link this account to the persistent cookie on this browser
        app(\App\Services\MultiAccountService::class)->addId($user->id);

        return app(LoginResponse::class);
    }

    protected function getRedirectUrl(): string
    {
        $redirectTo = session()->pull('auth.redirect_to');

        if (is_string($redirectTo) && $this->isSafeRedirect($redirectTo)) {
            return $redirectTo;
        }

        $user = auth()->user();

        if (! $user) {
            return '/admin';
        }

        if ($user->role === 'tutor') {
            return $user->tutorProfile()->exists()
                ? '/admin'
                : '/admin/tutor-profiles/create';
        }

        if ($user->role === 'admin') {
            return '/site-admin';
        }

        $hasLessons = $user->studentLessons()->exists() || $user->parentLessons()->exists();

        return $hasLessons ? '/admin/lessons' : '/tutors';
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login = trim((string) ($data['email'] ?? ''));

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => mb_strtolower($login),
                'password' => $data['password'],
            ];
        }

        return [
            'phone' => $this->normalizePhone($login),
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'data.email' => 'Неверный email/телефон или пароль.',
        ]);
    }

    private function resolveUserFromLogin(string $login): ?User
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($login)])
                ->first();
        }

        $normalizedPhone = $this->normalizePhone($login);

        return User::query()
            ->where('phone', $normalizedPhone)
            ->first();
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[^\d+]/', '', $phone) ?? $phone;

        if (str_starts_with($normalized, '375')) {
            return '+'.$normalized;
        }

        if (str_starts_with($normalized, '80')) {
            return '+375'.substr($normalized, 2);
        }

        return $normalized;
    }

    private function isSafeRedirect(string $redirectTo): bool
    {
        if (str_starts_with($redirectTo, '/')) {
            return true;
        }

        $host = parse_url($redirectTo, PHP_URL_HOST);

        return is_string($host) && in_array($host, [request()->getHost(), 'localhost', '127.0.0.1'], true);
    }
}
