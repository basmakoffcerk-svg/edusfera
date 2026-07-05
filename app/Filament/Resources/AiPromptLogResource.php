<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AiPromptLogResource\Pages;
use App\Models\AiPromptLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiPromptLogResource extends Resource
{
    protected static ?string $model = AiPromptLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Пользователь')
                    ->relationship('user', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('model')
                    ->label('Модель')
                    ->disabled(),
                Forms\Components\TextInput::make('tokens_used')
                    ->label('Токены')
                    ->disabled(),
                Forms\Components\TextInput::make('cost')
                    ->label('Стоимость ($)')
                    ->disabled(),
                Forms\Components\Toggle::make('is_error')
                    ->label('Ошибка')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Дата запроса')
                    ->disabled(),
                Forms\Components\Textarea::make('prompt')
                    ->label('Запрос')
                    ->rows(8)
                    ->columnSpanFull()
                    ->disabled(),
                Forms\Components\Textarea::make('response')
                    ->label('Ответ')
                    ->rows(8)
                    ->columnSpanFull()
                    ->disabled(),
                Forms\Components\Textarea::make('error_message')
                    ->label('Сообщение об ошибке')
                    ->rows(4)
                    ->columnSpanFull()
                    ->disabled()
                    ->visible(fn ($record) => $record?->is_error),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->default('Система')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Модель')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tokens_used')
                    ->label('Токены')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Затраты ($)')
                    ->formatStateUsing(fn ($state) => '$' . number_format((float)$state, 4))
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\IconColumn::make('is_error')
                    ->label('Статус')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAiPromptLogs::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Метрики и логи ИИ';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Управление ИИ и Моделями';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
