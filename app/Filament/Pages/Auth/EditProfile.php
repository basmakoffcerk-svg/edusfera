<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Фотография и личные данные')
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('Аватарка / Фото профиля')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->helperText('Квадратное или круглое фото. Отображается в шапке сайта, чатах и в анкете.'),
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        TextInput::make('phone')
                            ->label('Номер телефона')
                            ->tel()
                            ->maxLength(32),
                    ]),

                Section::make('Смена пароля')
                    ->description('Заполняйте только если хотите изменить текущий пароль.')
                    ->collapsed()
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),
            ]);
    }
}
