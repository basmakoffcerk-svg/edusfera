<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\HtmlString;

class Register extends BaseRegister
{
    protected static string $view = 'filament.admin.pages.auth.register';
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
                Wizard::make([
                    Wizard\Step::make('1. Роль')
                        ->description('Кто вы в Edusfera')
                        ->icon('heroicon-m-user-group')
                        ->schema([
                            Radio::make('role')
                                ->label('Я хочу зарегистрироваться как')
                                ->options([
                                    'tutor' => '👨‍🏫 Репетитор — Преподавание, белая оплата ЕРИП и ученики',
                                    'student' => '🎓 Ученик — Подготовка к ЦТ/ЦЭ с топовыми преподавателями',
                                    'parent' => '👨‍👩‍👧 Родитель — Безопасная оплата и отслеживание результатов',
                                ])
                                ->default('tutor')
                                ->required(),
                        ]),
                    Wizard\Step::make('2. Данные')
                        ->description('Контактная информация')
                        ->icon('heroicon-m-identification')
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
                        ]),
                    Wizard\Step::make('3. Пароль')
                        ->description('Безопасность и оферта')
                        ->icon('heroicon-m-lock-closed')
                        ->schema([
                            $this->getPasswordFormComponent(),
                            $this->getPasswordConfirmationFormComponent(),
                            Checkbox::make('terms')
                                ->label('Я согласен с условиями Публичной оферты')
                                ->required()
                                ->accepted(),
                        ]),
                ])
                ->submitAction(new HtmlString('
                    <button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary relative inline-flex items-center justify-center gap-1.5 rounded-xl px-6 py-3.5 text-base font-extrabold shadow-lg transition-all bg-violet-600 hover:bg-violet-500 text-white w-full uppercase tracking-wider">
                        Завершить регистрацию →
                    </button>
                ')),
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

        if ($user->isTutor()) {
            return $user->tutorProfile()->exists()
                ? '/admin'
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

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('Пароль')
            ->rules([
                \Illuminate\Validation\Rules\Password::min(8)
                    ->letters()
                    ->numbers()
                    ->uncompromised(2),
            ])
            ->validationMessages([
                'min' => 'Пароль должен быть не менее 8 символов и содержать буквы и цифры.',
                'uncompromised' => 'Этот пароль ранее встречался в утечках данных (credential stuffing). Выберите более надежный пароль.',
            ]);
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
