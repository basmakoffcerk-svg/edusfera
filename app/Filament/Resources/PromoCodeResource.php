<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\PromoCodeResource\Pages;
use App\Filament\Resources\PromoCodeResource\RelationManagers;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\PromoCodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Маркетинг и скидки';

    protected static ?string $modelLabel = 'Промокод';

    protected static ?string $pluralModelLabel = 'Промокоды';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Основная информация')
                            ->schema([
                                Forms\Components\TextInput::make('code')
                                    ->label('Код промокода')
                                    ->required()
                                    ->maxLength(64)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('EDU-XXXXXX')
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase; font-family: monospace; font-weight: bold; font-size: 1.1rem;'])
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('generateCode')
                                            ->icon('heroicon-m-sparkles')
                                            ->tooltip('Сгенерировать случайный промокод')
                                            ->action(function (Set $set) {
                                                $newCode = app(PromoCodeService::class)->generateUniqueCode();
                                                $set('code', $newCode);
                                            })
                                    ),

                                Forms\Components\TextInput::make('description')
                                    ->label('Описание / Назначение промокода')
                                    ->placeholder('Например: Скидка 15% на первое занятие для новых учеников')
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Промокод активен')
                                    ->default(true)
                                    ->helperText('Если выключено, промокод нельзя будет применить ни при каких условиях.'),
                            ])->columns(1),

                        Forms\Components\Section::make('Условия скидки / Бонуса')
                            ->schema([
                                Forms\Components\Radio::make('discount_type')
                                    ->label('Тип скидки / Бонуса')
                                    ->options([
                                        'percent' => 'Процент от суммы (%)',
                                        'fixed' => 'Фиксированная сумма (BYN)',
                                        'free_period' => 'Бесплатный период подписки репетитора',
                                        'lifetime' => 'Пожизненный бесплатный доступ к тарифу',
                                    ])
                                    ->default('percent')
                                    ->columns(2)
                                    ->live()
                                    ->required(),

                                Forms\Components\Select::make('subscription_period')
                                    ->label('Длительность бесплатной подписки')
                                    ->options([
                                        '1_month' => '1 месяц бесплатно',
                                        '3_months' => '3 месяца бесплатно',
                                        '6_months' => '6 месяцев (полгода) бесплатно',
                                        '12_months' => '12 месяцев (1 год) бесплатно',
                                    ])
                                    ->default('1_month')
                                    ->required(fn (Get $get): bool => $get('discount_type') === 'free_period')
                                    ->visible(fn (Get $get): bool => $get('discount_type') === 'free_period'),

                                Forms\Components\Select::make('subscription_plan')
                                    ->label('Тариф подписки')
                                    ->options([
                                        'pro' => 'Тариф «Про» (рекомендуется)',
                                        'premium' => 'Тариф «Премиум»',
                                        'basic' => 'Тариф «Стандарт»',
                                        'any' => 'Любой тариф (выбирает репетитор)',
                                    ])
                                    ->default('pro')
                                    ->required(fn (Get $get): bool => in_array($get('discount_type'), ['free_period', 'lifetime'], true))
                                    ->visible(fn (Get $get): bool => in_array($get('discount_type'), ['free_period', 'lifetime'], true)),

                                Forms\Components\TextInput::make('discount_value')
                                    ->label(fn (Get $get): string => $get('discount_type') === 'percent' ? 'Размер скидки (%)' : 'Сумма скидки (BYN)')
                                    ->numeric()
                                    ->required(fn (Get $get): bool => in_array($get('discount_type'), ['percent', 'fixed'], true))
                                    ->default(0.0)
                                    ->minValue(0.01)
                                    ->maxValue(fn (Get $get): ?float => $get('discount_type') === 'percent' ? 100.0 : null)
                                    ->suffix(fn (Get $get): string => $get('discount_type') === 'percent' ? '%' : 'BYN')
                                    ->visible(fn (Get $get): bool => in_array($get('discount_type'), ['percent', 'fixed'], true)),

                                Forms\Components\TextInput::make('max_discount_amount')
                                    ->label('Максимальный размер скидки (BYN)')
                                    ->helperText('Ограничение максимальной скидки в рублях при процентной скидке (оставьте пустым, если без лимита)')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->suffix('BYN')
                                    ->visible(fn (Get $get): bool => $get('discount_type') === 'percent'),

                                Forms\Components\TextInput::make('min_order_amount')
                                    ->label('Минимальная сумма заказа для применения (BYN)')
                                    ->helperText('Промокод сработает, только если исходная сумма заказа не меньше этой')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->suffix('BYN')
                                    ->visible(fn (Get $get): bool => in_array($get('discount_type'), ['percent', 'fixed'], true)),
                            ])->columns(2),

                        Forms\Components\Section::make('Область действия и фильтры')
                            ->schema([
                                Forms\Components\Select::make('scope')
                                    ->label('Где действует промокод')
                                    ->options([
                                        'all' => 'Везде (уроки и подписки репетиторов)',
                                        'lessons' => 'Только оплата занятий с репетитором',
                                        'subscriptions' => 'Только тарифные подписки репетиторов',
                                    ])
                                    ->default('all')
                                    ->live()
                                    ->required(),

                                Forms\Components\CheckboxList::make('package_codes')
                                    ->label('Применимо к форматам уроков')
                                    ->options([
                                        'single' => 'Разовое занятие',
                                        'pack_4' => 'Пакет из 4 уроков',
                                        'pack_8' => 'Пакет из 8 уроков',
                                    ])
                                    ->helperText('Если ничего не выбрано, действует для всех форматов')
                                    ->columns(3)
                                    ->visible(fn (Get $get): bool => $get('scope') !== 'subscriptions'),

                                Forms\Components\Select::make('subjects')
                                    ->label('Ограничение по предметам')
                                    ->options([
                                        'Математика' => 'Математика',
                                        'Физика' => 'Физика',
                                        'Химия' => 'Химия',
                                        'Биология' => 'Биология',
                                        'Английский язык' => 'Английский язык',
                                        'Русский язык' => 'Русский язык',
                                        'Белорусский язык' => 'Белорусский язык',
                                        'История' => 'История',
                                        'Информатика' => 'Информатика',
                                    ])
                                    ->multiple()
                                    ->searchable()
                                    ->helperText('Если не указано, скидка действует на все предметы')
                                    ->visible(fn (Get $get): bool => $get('scope') !== 'subscriptions'),

                                Forms\Components\Select::make('tutor_ids')
                                    ->label('Ограничение по конкретным репетиторам')
                                    ->options(fn () => User::query()
                                        ->where('role', UserRole::Tutor->value)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray()
                                    )
                                    ->multiple()
                                    ->searchable()
                                    ->helperText('Если не выбрано, действует для всех репетиторов платформы')
                                    ->visible(fn (Get $get): bool => $get('scope') !== 'subscriptions'),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Лимиты активаций')
                            ->schema([
                                Forms\Components\TextInput::make('max_uses')
                                    ->label('Общий лимит активаций')
                                    ->helperText('Сколько раз суммарно можно применить промокод (пусто = без лимита)')
                                    ->numeric()
                                    ->minValue(1),

                                Forms\Components\TextInput::make('max_uses_per_user')
                                    ->label('Лимит на 1 пользователя')
                                    ->default(1)
                                    ->required()
                                    ->numeric()
                                    ->minValue(1),

                                Forms\Components\Toggle::make('first_order_only')
                                    ->label('Только для первого заказа')
                                    ->helperText('Действует только для пользователей без ранее оплаченных заказов'),
                            ]),

                        Forms\Components\Section::make('Период действия')
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Действует с')
                                    ->timezone('Europe/Minsk')
                                    ->seconds(false),

                                Forms\Components\DateTimePicker::make('expires_at')
                                    ->label('Действует до')
                                    ->timezone('Europe/Minsk')
                                    ->seconds(false),
                            ]),

                        Forms\Components\Section::make('Статистика')
                            ->schema([
                                Forms\Components\Placeholder::make('used_count_display')
                                    ->label('Количество активаций')
                                    ->content(fn ($record): string => $record ? "{$record->used_count} ".($record->max_uses ? "из {$record->max_uses}" : 'раз') : '0'),

                                Forms\Components\Placeholder::make('created_by_display')
                                    ->label('Создатель')
                                    ->content(fn ($record): string => $record?->creator?->name ?? 'Администратор'),
                            ])
                            ->visible(fn ($record): bool => $record !== null),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Промокод')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Промокод скопирован в буфер')
                    ->weight('bold')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('discount_display')
                    ->label('Размер скидки / Бонус')
                    ->state(function (PromoCode $record): string {
                        if ($record->discount_type === 'percent') {
                            $text = "{$record->discount_value}%";
                            if ($record->max_discount_amount) {
                                $text .= " (макс. {$record->max_discount_amount} BYN)";
                            }

                            return $text;
                        }
                        if ($record->discount_type === 'free_period') {
                            $periodLabels = [
                                '1_month' => '1 мес. бесплатно',
                                '3_months' => '3 мес. бесплатно',
                                '6_months' => '6 мес. бесплатно',
                                '12_months' => '1 год бесплатно',
                            ];

                            return $periodLabels[$record->subscription_period] ?? 'Бесплатный период';
                        }
                        if ($record->discount_type === 'lifetime') {
                            return 'Бессрочный доступ (Lifetime)';
                        }

                        return sprintf('%.2f BYN', $record->discount_value);
                    })
                    ->badge()
                    ->color(fn (PromoCode $record): string => match ($record->discount_type) {
                        'lifetime' => 'warning',
                        'free_period' => 'info',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('scope')
                    ->label('Область')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => 'Везде',
                        'lessons' => 'Уроки',
                        'subscriptions' => 'Подписки',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'all' => 'primary',
                        'lessons' => 'info',
                        'subscriptions' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('usage_progress')
                    ->label('Использовано')
                    ->state(function (PromoCode $record): string {
                        if ($record->max_uses === null) {
                            return "{$record->used_count} / ∞";
                        }

                        return "{$record->used_count} / {$record->max_uses}";
                    })
                    ->badge()
                    ->color(fn (PromoCode $record): string => ($record->max_uses && $record->used_count >= $record->max_uses) ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->state(function (PromoCode $record): string {
                        if (! $record->is_active) {
                            return 'Отключен';
                        }
                        if ($record->starts_at && ! $record->starts_at->isPast() && ! $record->starts_at->isToday()) {
                            return 'Запланирован';
                        }
                        if ($record->expires_at && now()->gt($record->expires_at->copy()->endOfDay())) {
                            return 'Истёк';
                        }
                        if ($record->max_uses !== null && $record->used_count >= $record->max_uses) {
                            return 'Исчерпан';
                        }

                        return 'Активен';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Активен' => 'success',
                        'Запланирован' => 'info',
                        'Истёк', 'Исчерпан', 'Отключен' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Вкл/Выкл'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Истекает')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Бессрочно')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->placeholder('Все')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только отключенные'),

                Tables\Filters\SelectFilter::make('scope')
                    ->label('Область действия')
                    ->options([
                        'all' => 'Везде',
                        'lessons' => 'Уроки',
                        'subscriptions' => 'Подписки',
                    ]),

                Tables\Filters\SelectFilter::make('discount_type')
                    ->label('Тип скидки')
                    ->options([
                        'percent' => 'Процентная (%)',
                        'fixed' => 'Фиксированная (BYN)',
                        'free_period' => 'Бесплатный период подписки',
                        'lifetime' => 'Пожизненный бесплатный доступ',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Копировать')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()
                    ->modalHeading('Клонировать промокод')
                    ->modalDescription('Будет создана копия этого промокода с новым уникальным кодом.')
                    ->action(function (PromoCode $record) {
                        $newRecord = $record->replicate([
                            'used_count',
                            'created_at',
                            'updated_at',
                        ]);
                        $newRecord->code = app(PromoCodeService::class)->generateUniqueCode();
                        $newRecord->used_count = 0;
                        $newRecord->created_by = auth()->id();
                        $newRecord->save();

                        Notification::make()
                            ->title('Промокод успешно скопирован')
                            ->body("Создан новый промокод: {$newRecord->code}")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
