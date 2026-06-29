<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TutorProfileResource\Pages;
use App\Models\TutorProfile;
use App\Support\BynMoneyFormatter;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class TutorProfileResource extends Resource
{
    protected static ?string $model = TutorProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Профиль Репетитора';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId === 'site-admin') {
            return 'Анкеты репетиторов';
        }

        return self::isAdminContext()
            ? 'Анкеты репетиторов'
            : 'Профиль Репетитора';
    }

    public static function getNavigationGroup(): ?string
    {
        return (Filament::getCurrentPanel()?->getId() === 'site-admin' || Auth::user()?->role === 'admin')
            ? 'Модерация'
            : 'Профиль';
    }

    public static function getModelLabel(): string
    {
        return self::isAdminContext()
            ? 'Анкета репетитора'
            : 'Профиль репетитора';
    }

    public static function getPluralModelLabel(): string
    {
        return self::isAdminContext()
            ? 'Анкеты репетиторов'
            : 'Профили репетиторов';
    }

    /**
     * @return array<string, string>
     */
    private static function subjectsOptions(): array
    {
        return [
            'Математика' => 'Математика',
            'Физика' => 'Физика',
            'Химия' => 'Химия',
            'Биология' => 'Биология',
            'Английский язык' => 'Английский язык',
            'Русский язык' => 'Русский язык',
            'Белорусский язык' => 'Белорусский язык',
            'История' => 'История',
            'Информатика' => 'Информатика',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function audiencesOptions(): array
    {
        return [
            '5-8 классы' => '5-8 классы',
            '9-11 классы' => '9-11 классы',
            'Подготовка к ЦТ' => 'Подготовка к ЦТ',
            'Подготовка к ЦЭ' => 'Подготовка к ЦЭ',
            'Студенты' => 'Студенты',
        ];
    }

    private static function priceHint(Get $get): HtmlString
    {
        $subjects = (array) ($get('subjects') ?? []);
        $audiences = (array) ($get('audiences') ?? []);
        $isExamTrack = in_array('Подготовка к ЦТ', $audiences, true) || in_array('Подготовка к ЦЭ', $audiences, true);
        $icon = self::iconHtml();

        if (in_array('Математика', $subjects, true) && $isExamTrack) {
            return new HtmlString("Средняя цена по Минску для подготовки к ЦТ/ЦЭ по математике — 40&nbsp;{$icon}. Для старта поставьте 35-40&nbsp;{$icon}, чтобы быстрее получить первые отзывы.");
        }

        if (in_array('Английский язык', $subjects, true)) {
            return new HtmlString("Средняя цена по Минску для английского — 35-45&nbsp;{$icon}. Для первых заявок обычно хорошо работает диапазон 32-38&nbsp;{$icon}.");
        }

        if (in_array('Физика', $subjects, true)) {
            return new HtmlString("Средняя цена по Минску для физики — 38-48&nbsp;{$icon}. Для старта можно поставить 35-40&nbsp;{$icon}.");
        }

        return new HtmlString('Для быстрого старта поставьте цену немного ниже средней по рынку, а после первых отзывов поднимите ставку.');
    }

    private static function iconHtml(): string
    {
        return '<img src="'.e(asset('byn-ico.svg')).'" alt="" aria-hidden="true" style="display:inline-block;width:0.81em;height:1em;vertical-align:-0.12em">';
    }

    private static function isAdminContext(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function tutorSchema(): array
    {
        return [
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Основная информация')
                    ->description('Шаг 1 из 3')
                    ->schema([
                        Forms\Components\Placeholder::make('welcome_hook')
                            ->label('')
                            ->content(new HtmlString(
                                '<div style="padding: 1rem 0;">
                                    <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 .5rem;">Добро пожаловать! Давайте создадим ваш профиль.</h2>
                                    <p style="margin: 0; color: #6b7280;">Это основная информация, которую родители увидят в каталоге репетиторов.</p>
                                </div>'
                            )),
                        Forms\Components\FileUpload::make('avatar_path')
                            ->label('Портретное фото')
                            ->image()
                            ->directory('avatars')
                            ->avatar()
                            ->imageEditor()
                            ->required()
                            ->helperText('Выбирайте светлое фото, где хорошо видно лицо. Это сильно повышает доверие.'),
                        Forms\Components\Select::make('subjects')
                            ->label('Предметы')
                            ->multiple()
                            ->options(self::subjectsOptions())
                            ->required()
                            ->helperText('Выберите основные предметы для преподавания.'),
                        Forms\Components\CheckboxList::make('audiences')
                            ->label('Классы и аудитория')
                            ->options(self::audiencesOptions())
                            ->columns(2)
                            ->required(),
                        Forms\Components\CheckboxList::make('lesson_formats')
                            ->label('Форматы занятий')
                            ->options([
                                'individual_online' => 'Индивидуально онлайн',
                                'mini_group_online' => 'Мини-группа онлайн',
                                'intensive' => 'Интенсив перед экзаменом',
                                'long_term' => 'Долгосрочное сопровождение',
                            ])
                            ->columns(2),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_per_hour')
                                    ->label('Цена за час')
                                    ->numeric()
                                    ->prefix(new HtmlString(self::iconHtml()))
                                    ->minValue(1)
                                    ->required()
                                    ->helperText(fn (Get $get): HtmlString => self::priceHint($get)),
                                Forms\Components\TextInput::make('experience_years')
                                    ->label('Стаж (лет)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Wizard\Step::make('Презентация и методика')
                    ->description('Шаг 2 из 3')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->label('О себе')
                            ->rows(6)
                            ->required()
                            ->placeholder("1. Какой у вас опыт.\n2. Какие средние баллы у учеников.\n3. Как проходит урок."),
                        Forms\Components\Textarea::make('education_summary')
                            ->label('Образование и квалификация')
                            ->rows(4)
                            ->placeholder('ВУЗ, специальность, год выпуска, профильные курсы.'),
                        Forms\Components\Textarea::make('achievements')
                            ->label('Результаты учеников')
                            ->rows(4)
                            ->placeholder('Кейсы поступления, олимпиадные результаты.'),
                        Forms\Components\Textarea::make('teaching_methodology')
                            ->label('Методика занятий')
                            ->rows(4)
                            ->placeholder('Как строите урок, даете домашние задания.'),

                        Forms\Components\Section::make('Дополнительные настройки')
                            ->description('Эти поля помогут выделить ваш профиль, но они не обязательны.')
                            ->collapsed()
                            ->schema([
                                Forms\Components\TextInput::make('telegram_username')
                                    ->label('Telegram username')
                                    ->prefix('@')
                                    ->placeholder('edusfera_tutor')
                                    ->helperText('Необязательно. Откроется ученику в чате после успешной оплаты.'),
                                Forms\Components\CheckboxList::make('lesson_languages')
                                    ->label('Языки преподавания')
                                    ->options([
                                        'ru' => 'Русский',
                                        'be' => 'Белорусский',
                                        'en' => 'Английский',
                                    ])
                                    ->columns(3),
                                Forms\Components\CheckboxList::make('exam_specializations')
                                    ->label('Экзаменационные специализации')
                                    ->options([
                                        'ЦЭ' => 'Подготовка к ЦЭ',
                                        'ЦТ' => 'Подготовка к ЦТ',
                                        'intensive' => 'Экзаменационный интенсив',
                                        'score_growth' => 'Рост балла за 8-12 недель',
                                    ])
                                    ->columns(2),
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('average_score_growth')
                                            ->label('Средний рост балла')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->placeholder('Например, 18'),
                                        Forms\Components\TextInput::make('students_prepared_count')
                                            ->label('Учеников подготовлено')
                                            ->numeric()
                                            ->minValue(0)
                                            ->placeholder('Например, 24'),
                                        Forms\Components\TextInput::make('max_recent_score')
                                            ->label('Лучший недавний результат')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->placeholder('Например, 92'),
                                    ]),
                                Forms\Components\Toggle::make('diagnostic_supported')
                                    ->label('Поддерживает стартовую диагностику и работу по слабым темам')
                                    ->inline(false),
                                Forms\Components\Textarea::make('homework_policy')
                                    ->label('Домашние задания и обратная связь')
                                    ->rows(3),
                                Forms\Components\TextInput::make('intro_video_url')
                                    ->label('Ссылка на видео-визитку')
                                    ->url()
                                    ->placeholder('https://youtu.be/...'),
                                Forms\Components\TextInput::make('trial_lesson_minutes')
                                    ->label('Пробный созвон (минут)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(120),
                            ]),
                    ]),

                Forms\Components\Wizard\Step::make('Верификация')
                    ->description('Шаг 3 из 3')
                    ->schema([
                        Forms\Components\Select::make('legal_status')
                            ->label('Юридический статус')
                            ->options([
                                'npd' => 'НПД (Налог на проф. доход)',
                                'ip' => 'ИП',
                                'self_employed' => 'Самозанятый',
                                'none' => 'Нет статуса (Физ. лицо)',
                            ])
                            ->required(),
                        Forms\Components\FileUpload::make('diploma_path')
                            ->label('Диплом / сертификат')
                            ->directory('diplomas')
                            ->required()
                            ->helperText("Документы не публикуются. Нужны модератору для бейджа '✓ Проверенный специалист'."),
                        Forms\Components\Checkbox::make('verification_consent')
                            ->label("Подтверждаю, что документы верны, и согласен с проверкой для получения бейджа '✓ Проверенный специалист'")
                            ->accepted()
                            ->dehydrated(false)
                            ->required(),
                        Forms\Components\Placeholder::make('verification_microcopy')
                            ->label('')
                            ->content('Профили с бейджем в среднем получают больше заявок, потому что родители видят подтвержденную квалификацию.'),
                    ]),
            ])->submitAction(new HtmlString('<button type="submit" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 dark:bg-custom-500 dark:hover:bg-custom-400 focus-visible:ring-custom-500/50 dark:focus-visible:ring-custom-400/50 fi-ac-btn-action" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"><span class="fi-btn-label">Сохранить</span></button>'))->columnSpanFull(),

            Forms\Components\Section::make('Модерация (Только для Админов)')
                ->visible(fn () => Auth::user()?->role === 'admin')
                ->schema([
                    Forms\Components\Toggle::make('is_verified')
                        ->label('Верифицирован'),
                    Forms\Components\Select::make('verification_status')
                        ->label('Статус модерации')
                        ->options([
                            'pending' => 'На проверке',
                            'approved' => 'Одобрен',
                            'rejected' => 'Отклонен',
                        ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function adminSchema(): array
    {
        return [
            Forms\Components\Section::make('Анкета преподавателя')
                ->description('Проверьте содержимое анкеты, документы и примите решение по модерации.')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Placeholder::make('moderation_name')
                                ->label('Имя')
                                ->content(fn (?TutorProfile $record): string => $record?->user?->name ?? 'Не указано'),
                            Forms\Components\Placeholder::make('moderation_email')
                                ->label('Email')
                                ->content(fn (?TutorProfile $record): string => $record?->user?->email ?? 'Не указан'),
                            Forms\Components\Placeholder::make('moderation_phone')
                                ->label('Телефон')
                                ->content(fn (?TutorProfile $record): string => $record?->user?->phone ?? 'Не указан'),
                            Forms\Components\Placeholder::make('moderation_telegram')
                                ->label('Telegram')
                                ->content(fn (?TutorProfile $record): string => $record?->telegram_username ? '@'.ltrim($record->telegram_username, '@') : 'Не указан'),
                            Forms\Components\Placeholder::make('moderation_subjects')
                                ->label('Предметы')
                                ->content(fn (?TutorProfile $record): string => implode(', ', $record?->subjects ?? []) ?: 'Не указаны'),
                            Forms\Components\Placeholder::make('moderation_audiences')
                                ->label('Аудитория')
                                ->content(fn (?TutorProfile $record): string => implode(', ', $record?->audiences ?? []) ?: 'Не указана'),
                            Forms\Components\Placeholder::make('moderation_price')
                                ->label('Цена')
                                ->content(fn (?TutorProfile $record): HtmlString|string => $record
                                    ? new HtmlString(BynMoneyFormatter::format((string) $record->price_per_hour)->toHtml().'/час')
                                    : 'Не указана'),
                            Forms\Components\Placeholder::make('moderation_experience')
                                ->label('Стаж')
                                ->content(fn (?TutorProfile $record): string => $record ? ((int) $record->experience_years).' лет' : 'Не указан'),
                            Forms\Components\Placeholder::make('moderation_status')
                                ->label('Юридический статус')
                                ->content(fn (?TutorProfile $record): string => match ($record?->legal_status) {
                                    'npd' => 'НПД',
                                    'ip' => 'ИП',
                                    'self_employed' => 'Самозанятый',
                                    'none' => 'Нет статуса',
                                    default => 'Не указан',
                                }),
                            Forms\Components\Placeholder::make('moderation_submitted')
                                ->label('Отправлено на проверку')
                                ->content(fn (?TutorProfile $record): string => $record?->verification_submitted_at?->timezone(config('booking.display_timezone'))->format('d.m.Y H:i') ?? 'Неизвестно'),
                        ]),
                    Forms\Components\Textarea::make('bio')
                        ->label('Описание анкеты')
                        ->rows(6)
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\FileUpload::make('avatar_path')
                                ->label('Фото профиля')
                                ->image()
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\FileUpload::make('diploma_path')
                                ->label('Диплом / сертификат')
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                ]),

            Forms\Components\Section::make('Решение модератора')
                ->schema([
                    Forms\Components\Select::make('verification_status')
                        ->label('Статус модерации')
                        ->options([
                            'pending' => 'На проверке',
                            'approved' => 'Одобрен',
                            'rejected' => 'Отклонен',
                        ])
                        ->required()
                        ->native(false)
                        ->helperText('Решение влияет на видимость анкеты в каталоге и возможность бронирования.'),
                    Forms\Components\Toggle::make('is_verified')
                        ->label('Профиль доступен в каталоге')
                        ->helperText('Поле синхронизируется автоматически по статусу модерации.'),
                ]),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema(
            self::isAdminContext()
                ? self::adminSchema()
                : self::tutorSchema()
        );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_path')
                    ->label('Фото')
                    ->circular(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('subjects')
                    ->label('Предметы')
                    ->formatStateUsing(function (array|string|null $state): string {
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            $state = is_array($decoded) ? $decoded : [$state];
                        }

                        return implode(', ', $state ?? []);
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('price_per_hour')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state): HtmlString => BynMoneyFormatter::format((string) $state)),
                Tables\Columns\TextColumn::make('verification_status')
                    ->label('Модерация')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Одобрен',
                        'rejected' => 'Отклонен',
                        default => 'На проверке',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Статус')
                    ->boolean(),
                Tables\Columns\TextColumn::make('contact_bypass_attempts')
                    ->label('Риски')
                    ->badge()
                    ->color(fn (int $state): string => $state >= 3 ? 'danger' : ($state > 0 ? 'warning' : 'gray'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('verification_submitted_at')
                    ->label('Отправлено')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')->label('Верифицирован'),
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Статус модерации')
                    ->options([
                        'pending' => 'На проверке',
                        'approved' => 'Одобрен',
                        'rejected' => 'Отклонен',
                    ]),
                Tables\Filters\Filter::make('high_risk')
                    ->label('Только рисковые')
                    ->query(fn (Builder $query): Builder => $query->where('contact_bypass_attempts', '>', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (): bool => self::isAdminContext())
                    ->requiresConfirmation()
                    ->action(function (TutorProfile $record): void {
                        $record->update([
                            'verification_status' => 'approved',
                            'is_verified' => true,
                        ]);

                        $record->user?->update([
                            'is_verified' => true,
                        ]);

                        Notification::make()
                            ->title('Анкета одобрена')
                            ->body('Профиль опубликован в каталоге.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (): bool => self::isAdminContext())
                    ->requiresConfirmation()
                    ->action(function (TutorProfile $record): void {
                        $record->update([
                            'verification_status' => 'rejected',
                            'is_verified' => false,
                        ]);

                        $record->user?->update([
                            'is_verified' => false,
                        ]);

                        Notification::make()
                            ->title('Анкета отклонена')
                            ->body('Профиль снят с публикации.')
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve_selected')
                    ->label('Одобрить выбранные')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (): bool => self::isAdminContext())
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        foreach ($records as $record) {
                            $record->update([
                                'verification_status' => 'approved',
                                'is_verified' => true,
                            ]);

                            $record->user?->update([
                                'is_verified' => true,
                            ]);
                        }

                        Notification::make()
                            ->title('Выбранные анкеты одобрены')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('reject_selected')
                    ->label('Отклонить выбранные')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (): bool => self::isAdminContext())
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        foreach ($records as $record) {
                            $record->update([
                                'verification_status' => 'rejected',
                                'is_verified' => false,
                            ]);

                            $record->user?->update([
                                'is_verified' => false,
                            ]);
                        }

                        Notification::make()
                            ->title('Выбранные анкеты отклонены')
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user?->role === 'tutor') {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTutorProfiles::route('/'),
            'create' => Pages\CreateTutorProfile::route('/create'),
            'edit' => Pages\EditTutorProfile::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return $user?->role === 'tutor' || $user?->role === 'admin';
    }
}
