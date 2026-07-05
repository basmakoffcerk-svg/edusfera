<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BepaidWebhookLogResource\Pages;
use App\Models\BepaidWebhookLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BepaidWebhookLogResource extends Resource
{
    protected static ?string $model = BepaidWebhookLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?int $navigationSort = 60;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('event')
                    ->label('Событие')
                    ->disabled(),
                Forms\Components\TextInput::make('transaction_id')
                    ->label('ID транзакции')
                    ->disabled(),
                Forms\Components\TextInput::make('ip')
                    ->label('IP адрес')
                    ->disabled(),
                Forms\Components\TextInput::make('status_code')
                    ->label('HTTP Статус')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Время получения')
                    ->disabled(),
                Forms\Components\Textarea::make('error_reason')
                    ->label('Ошибка')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('payload')
                    ->label('Данные запроса (JSON Payload)')
                    ->columnSpanFull()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->label('Событие')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'payment.success', 'payment.completed' => 'success',
                        'payment.failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Транзакция')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_code')
                    ->label('HTTP Статус')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state === 200 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('error_reason')
                    ->label('Ошибка')
                    ->limit(35),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата получения')
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
            'index' => Pages\ManageBepaidWebhookLogs::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Лог эквайринга (bePaid)';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Финансы';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'admin';
    }
}
