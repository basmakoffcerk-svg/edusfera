<?php

declare(strict_types=1);

namespace App\Filament\SiteAdmin\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;

class Login extends \Filament\Pages\Auth\Login
{
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
            ->label('Технический email')
            ->email()
            ->required()
            ->autofocus()
            ->autocomplete('username');
    }

    protected function getRedirectUrl(): string
    {
        return '/site-admin';
    }
}
