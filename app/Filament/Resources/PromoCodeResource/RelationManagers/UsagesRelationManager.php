<?php

declare(strict_types=1);

namespace App\Filament\Resources\PromoCodeResource\RelationManagers;

use App\Models\PromoCodeUsage;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    protected static ?string $title = 'История активаций промокода';

    protected static ?string $modelLabel = 'Активация';

    protected static ?string $pluralModelLabel = 'Активации';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата и время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable()
                    ->description(fn (PromoCodeUsage $record): string => $record->user?->email ?? ''),

                Tables\Columns\TextColumn::make('order_type')
                    ->label('Тип заказа')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lesson' => 'Урок',
                        'subscription' => 'Подписка репетитора',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'lesson' => 'info',
                        'subscription' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('lesson.id')
                    ->label('ID Урока')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('original_amount')
                    ->label('Исходная сумма')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ' ').' BYN'),

                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Скидка')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ' ').' BYN')
                    ->color('success'),

                Tables\Columns\TextColumn::make('final_amount')
                    ->label('Оплачено')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ' ').' BYN')
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_type')
                    ->label('Тип заказа')
                    ->options([
                        'lesson' => 'Урок',
                        'subscription' => 'Подписка репетитора',
                    ]),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
