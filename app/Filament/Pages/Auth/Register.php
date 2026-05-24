<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;

class Register extends BaseRegister
{
    protected static string $view = 'filament.admin.pages.auth.register';

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
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->placeholder('+375 (XX) XXX-XX-XX')
                    ->tel()
                    ->required()
                    ->inputMode('tel')
                    ->unique('users', 'phone')
                    ->dehydrateStateUsing(fn (string $state): string => $this->normalizePhone($state))
                    ->regex('/^\+375\d{9}$/')
                    ->validationMessages([
                        'unique' => 'Пользователь с таким номером телефона уже зарегистрирован.',
                        'regex' => 'Телефон должен быть в формате +375XXXXXXXXX.',
                    ])
                    ->helperText('Формат: +375XXXXXXXXX (9 цифр после кода)'),
                Select::make('role')
                    ->label('Я хочу зарегистрироваться как')
                    ->options([
                        'tutor' => 'Репетитор',
                        'student' => 'Ученик',
                        'parent' => 'Родитель',
                    ])
                    ->required(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                Checkbox::make('terms')
                    ->label('Я согласен с условиями Публичной оферты')
                    ->required()
                    ->accepted(),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->label('Адрес электронной почты')
            ->dehydrateStateUsing(fn (string $state): string => mb_strtolower(trim($state)))
            ->validationMessages([
                'unique' => 'Пользователь с таким email уже зарегистрирован.',
                'email' => 'Введите корректный email.',
            ]);
    }

    protected function handleRegistration(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'offer_accepted_at' => now(),
        ]);

        $user->forceFill([
            'role' => $data['role'],
            'is_verified' => false,
        ])->save();

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        $redirectTo = session()->pull('auth.redirect_to');

        if (is_string($redirectTo) && $this->isSafeRedirect($redirectTo)) {
            return $redirectTo;
        }

        $user = auth()->user();

        if ($user->role === 'tutor') {
            return $user->tutorProfile()->exists()
                ? '/admin/tutor-profiles'
                : '/admin/tutor-profiles/create';
        }

        return '/tutors';
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
        if (str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//')) {
            return true;
        }

        $host = parse_url($redirectTo, PHP_URL_HOST);

        return is_string($host) && in_array($host, [request()->getHost(), 'localhost', '127.0.0.1'], true);
    }
}
