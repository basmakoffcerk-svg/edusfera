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
        return Filament::getCurrentPanel()?->getId() === 'site-admin'
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

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'tutor';
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
                Forms\Components\Wizard\Step::make('Приветствие')
                    ->description('Шаг 1 из 4')
                    ->schema([
                        Forms\Components\Placeholder::make('welcome_hook')
                            ->label('')
                            ->content(new HtmlString(
                                '<div style="padding: 1rem 0;">
                                    <h2 style="font-size: 1.7rem; font-weight: 800; margin: 0 0 .75rem;">Добро пожаловать в Edusfera! До первых учеников осталось 3 шага.</h2>
                                    <p style="margin: 0; color: #6b7280; line-height: 1.8;">Мы берем на себя поиск клиентов, платежи и чеки, чтобы вы могли просто преподавать. Заполнение займет не более 15 минут.</p>
                                </div>'
                            )),
                    ]),

                Forms\Components\Wizard\Step::make('Базовые настройки')
                    ->description('Шаг 2 из 4')
                    ->schema([
                        Forms\Components\Select::make('subjects')
                            ->label('Предметы')
                            ->multiple()
                            ->options(self::subjectsOptions())
                            ->required()
                            ->helperText('Выберите основные предметы. Эти теги показываются в каталоге.'),
                        Forms\Components\CheckboxList::make('audiences')
                            ->label('Классы и аудитория')
                            ->options(self::audiencesOptions())
                            ->columns(2)
                            ->helperText('От этого зависит фильтрация в каталоге для родителей.')
                            ->required(),
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

                Forms\Components\Wizard\Step::make('Визуальное доверие')
                    ->description('Шаг 3 из 4')
                    ->schema([
                        Forms\Components\Placeholder::make('photo_guideline')
                            ->label('Рекомендации по фото')
                            ->content(new HtmlString(
                                '<div style="display:grid;gap:.75rem;">
                                    <div style="padding:.75rem;border-radius:.75rem;background:#ecfdf5;border:1px solid #a7f3d0;"><strong>Good:</strong> светлый портрет, лицо крупно, нейтральный фон.</div>
                                    <div style="padding:.75rem;border-radius:.75rem;background:#fff1f2;border:1px solid #fecdd3;"><strong>Bad:</strong> темное фото, размыто, селфи с отвлекающим фоном.</div>
                                </div>'
                            )),
                        Forms\Components\FileUpload::make('avatar_path')
                            ->label('Портретное фото')
                            ->image()
                            ->directory('avatars')
                            ->avatar()
                            ->imageEditor()
                            ->required()
                            ->helperText('Родители сначала смотрят на фото.'),
                        Forms\Components\TextInput::make('telegram_username')
                            ->label('Telegram username')
                            ->prefix('@')
                            ->placeholder('edusfera_tutor')
                            ->helperText('Необязательно. Откроется ученику в чате после успешной оплаты.'),
                        Forms\Components\Textarea::make('bio')
                            ->label('О себе')
                            ->rows(6)
                            ->required()
                            ->placeholder("1. Какой у вас опыт.\n2. Какие средние баллы на ЦТ/ЦЭ у ваших учеников.\n3. Как проходит урок (Zoom/Skype, даете ли конспекты)."),
                        Forms\Components\Textarea::make('education_summary')
                            ->label('Образование и квалификация')
                            ->rows(4)
                            ->placeholder('ВУЗ, специальность, год выпуска, профильные курсы.'),
                        Forms\Components\Textarea::make('achievements')
                            ->label('Результаты учеников')
                            ->rows(4)
                            ->placeholder('Средний прирост балла, кейсы поступления, олимпиадные результаты.'),
                        Forms\Components\Textarea::make('teaching_methodology')
                            ->label('Методика занятий')
                            ->rows(4)
                            ->placeholder('Как строите урок, как даете домашние задания, как отслеживаете прогресс.'),
                        Forms\Components\CheckboxList::make('lesson_formats')
                            ->label('Форматы занятий')
                            ->options([
                                'individual_online' => 'Индивидуально онлайн',
                                'mini_group_online' => 'Мини-группа онлайн',
                                'intensive' => 'Интенсив перед экзаменом',
                                'long_term' => 'Долгосрочное сопровождение',
                            ])
                            ->columns(2),
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
                            ->columns(2)
                            ->helperText('Эти метки используются в каталоге для целевого подбора родителей и учеников.'),
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

                Forms\Components\Wizard\Step::make('Верификация')
                    ->description('Шаг 4 из 4')
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
            ])->columnSpanFull(),

            Forms\Components\Section::make('Модерация (Только для Админов)')
                ->schema([
                    Forms\Components\Toggle::make('is_verified')
                        ->label('Верифицирован')
                        ->visible(fn () => Auth::user()->role === 'admin'),
                    Forms\Components\Select::make('verification_status')
                        ->label('Статус модерации')
                        ->options([
                            'pending' => 'На проверке',
                            'approved' => 'Одобрен',
                            'rejected' => 'Отклонен',
                        ])
                        ->visible(fn () => Auth::user()->role === 'admin'),
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
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId === 'site-admin') {
            return $user?->role === 'admin';
        }

        return $user?->role === 'tutor';
    }
}
