<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\MultiAccountService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Login extends \Filament\Pages\Auth\Login
{
    protected static string $view = 'filament.admin.pages.auth.login';

    protected static string $layout = 'filament-panels::components.layout.base';

    public function mount(): void
    {
        parent::mount();

        $redirectTo = request()->query('redirect_to');

        if (is_string($redirectTo) && $this->isSafeRedirect($redirectTo)) {
            session(['auth.redirect_to' => $redirectTo]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->inlineLabel(false)
            ->columns(1)
            ->statePath('data');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email или телефон')
            ->placeholder('name@example.com или +375XXXXXXXXX')
            ->required()
            ->inlineLabel(false)
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
            ->label('Пароль')
            ->inlineLabel(false)
            ->revealable()
            ->autocomplete('current-password')
            ->extraInputAttributes([
                'tabindex' => 2,
            ]);
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Войти')
            ->submit('authenticate');
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
        $passwordValid = false;
        if ($user && Hash::check((string) ($data['password'] ?? ''), (string) $user->password)) {
            $passwordValid = true;
        }

        if (! $user || ! $passwordValid) {
            // Anti-Brute force: задержка при неверном пароле для защиты от автоматизированного перебора
            usleep(300000);
            $this->throwFailureValidationException();
        }

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            usleep(300000);
            $this->throwFailureValidationException();
        }

        Filament::auth()->login($user, (bool) ($data['remember'] ?? false));
        session()->regenerate();

        // Automatically link this account to the persistent cookie on this browser
        app(MultiAccountService::class)->addId($user->id);

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

        if ($user->isTutor()) {
            $sub = $user->subscription;
            if (! $sub || ! $sub->isActive() || ! $sub->is_onboarded) {
                return route('filament.admin.pages.tutor-subscription-page');
            }

            return $user->tutorProfile()->exists()
                ? '/admin'
                : '/admin/tutor-profiles/create';
        }

        if ($user->isAdmin()) {
            return '/admin';
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
        throw ValidationException::withMessages([
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

        $cleanLogin = mb_strtolower($login);

        // Handle Admin & TechAdmin aliases
        if (in_array($cleanLogin, ['admin', 'administrator', 'админ', 'администратор'], true)) {
            return User::query()
                ->where('email', 'admin@edusfera.by')
                ->orWhere(function ($q) {
                    $q->where('role', 'admin')->orWhere('role', UserRole::Admin);
                })
                ->first();
        }

        if (in_array($cleanLogin, ['tech-admin', 'tech_admin', 'techadmin', 'техадмин', 'техадминистратор'], true)) {
            $techEmail = mb_strtolower((string) config('site_admin.email', 'tech-admin@edusfera.by'));

            return User::query()->where('email', $techEmail)->first()
                ?? User::query()->where('email', 'tech-admin@edusfera.by')->first();
        }

        $byPrefix = User::query()->whereRaw('LOWER(email) = ?', [$cleanLogin.'@edusfera.by'])->first()
            ?? User::query()->whereRaw('LOWER(email) LIKE ?', [$cleanLogin.'@%'])->first();

        if ($byPrefix) {
            return $byPrefix;
        }

        $normalizedPhone = $this->normalizePhone($login);
        $rawDigits = preg_replace('/\D/', '', $login) ?? '';

        if ($normalizedPhone !== '' && $rawDigits !== '') {
            return User::query()
                ->where('phone', $normalizedPhone)
                ->orWhere('phone', ltrim($normalizedPhone, '+'))
                ->orWhere('phone', $rawDigits)
                ->first();
        }

        return null;
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
        if (str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//') && ! str_starts_with($redirectTo, '/\\')) {
            return true;
        }

        $host = parse_url($redirectTo, PHP_URL_HOST);

        return is_string($host) && in_array($host, [request()->getHost(), 'localhost', '127.0.0.1'], true);
    }
}
