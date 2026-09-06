<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основные данные пользователя')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('ФИО / Имя')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Номер телефона')
                            ->tel()
                            ->maxLength(32),

                        Forms\Components\Select::make('role')
                            ->label('Роль на платформе')
                            ->options([
                                'student' => 'Ученик',
                                'tutor' => 'Репетитор',
                                'parent' => 'Родитель',
                                'admin' => 'Администратор',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Безопасность и пароль')
                    ->schema([
                        Forms\Components\TextInput::make('new_password')
                            ->label('Новый пароль')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->helperText('Заполняйте только при необходимости сброса/изменения пароля'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof UserRole ? $state->value : (string) $state) {
                        'admin' => 'danger',
                        'tutor' => 'warning',
                        'student' => 'success',
                        'parent' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state instanceof UserRole ? $state->value : (string) $state) {
                        'admin' => 'Администратор',
                        'tutor' => 'Репетитор',
                        'student' => 'Ученик',
                        'parent' => 'Родитель',
                        default => (string) $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Фильтр по роли')
                    ->options([
                        'student' => 'Ученики',
                        'tutor' => 'Репетиторы',
                        'parent' => 'Родители',
                        'admin' => 'Администраторы',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('changePassword')
                    ->label('Сменить пароль')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading(fn (User $record): string => "Смена пароля: {$record->name}")
                    ->modalDescription(fn (User $record): string => "Укажите новый пароль для пользователя {$record->email}")
                    ->form([
                        Forms\Components\TextInput::make('new_password')
                            ->label('Новый пароль')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'password' => Hash::make($data['new_password']),
                        ]);

                        Notification::make()
                            ->title('Пароль успешно изменён!')
                            ->body("Новый пароль установлен для {$record->email}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Все пользователи';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Управление пользователями';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
