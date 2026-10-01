<?php

declare(strict_types=1);

namespace App\Filament\SiteAdmin\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;

class Login extends \App\Filament\Pages\Auth\Login
{
    protected static string $view = 'filament.admin.pages.auth.login';

    protected static string $layout = 'filament-panels::components.layout.base';

    public function getHeading(): string
    {
        return 'Панель модерации';
    }

    public function getSubHeading(): ?string
    {
        return 'Вход по техническим учетным данным администратора.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Логин / Email')
            ->required()
            ->autofocus()
            ->autocomplete('username');
    }

    protected function getRedirectUrl(): string
    {
        return '/site-admin';
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        try {
            return parent::authenticate();
        } catch (ValidationException $e) {
            // Anti-Brute force delay on failed admin login attempts
            usleep(300000);
            throw $e;
        }
    }
}
