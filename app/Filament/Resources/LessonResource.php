<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Notifications\LessonCancelledNotification;
use App\Notifications\LessonConfirmedNotification;
use App\Support\BynMoneyFormatter;
use App\Services\ChatService;
use App\Services\Payment\PaymentService;
use App\Services\PostLessonReportService;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Детали встречи')
                ->schema([
                    Forms\Components\TextInput::make('meeting_link')
                        ->label('Ссылка на встречу')
                        ->url()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('notes')
                        ->label('Комментарий')
                        ->rows(4)
                        ->disabled(),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(3)
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\Section::make('Основная информация')
                                ->schema([
                                    Infolists\Components\TextEntry::make('status')
                                        ->label('Статус занятия')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            Lesson::STATUS_PENDING => 'warning',
                                            Lesson::STATUS_CONFIRMED => 'success',
                                            Lesson::STATUS_CANCELLED => 'danger',
                                            Lesson::STATUS_COMPLETED => 'gray',
                                            Lesson::STATUS_NO_SHOW => 'danger',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            Lesson::STATUS_PENDING => 'Ожидает',
                                            Lesson::STATUS_CONFIRMED => 'Подтвержден',
                                            Lesson::STATUS_CANCELLED => 'Отменен',
                                            Lesson::STATUS_COMPLETED => 'Завершен',
                                            Lesson::STATUS_NO_SHOW => 'Неявка',
                                            default => $state,
                                        }),
                                    Infolists\Components\TextEntry::make('payment_status')
                                        ->label('Статус оплаты')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            Lesson::PAYMENT_PAID => 'success',
                                            Lesson::PAYMENT_UNPAID => 'warning',
                                            Lesson::PAYMENT_REFUNDED, Lesson::PAYMENT_PARTIALLY_REFUNDED => 'danger',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            Lesson::PAYMENT_UNPAID => 'Не оплачено',
                                            Lesson::PAYMENT_PAID => 'Оплачено',
                                            Lesson::PAYMENT_REFUNDED => 'Возврат',
                                            Lesson::PAYMENT_PARTIALLY_REFUNDED => 'Частичный возврат',
                                            default => $state,
                                        }),
                                    Infolists\Components\TextEntry::make('start_time')
                                        ->label('Начало')
                                        ->dateTime('d.m.Y H:i', config('booking.display_timezone')),
                                    Infolists\Components\TextEntry::make('end_time')
                                        ->label('Конец')
                                        ->dateTime('d.m.Y H:i', config('booking.display_timezone')),
                                    Infolists\Components\TextEntry::make('duration_minutes')
                                        ->label('Длительность')
                                        ->suffix(' минут'),
                                ]),

                            Infolists\Components\Section::make('Дополнительно')
                                ->schema([
                                    Infolists\Components\TextEntry::make('meeting_link')
                                        ->label('Ссылка на встречу')
                                        ->url(fn ($state) => $state)
                                        ->openUrlInNewTab()
                                        ->placeholder('Не указана')
                                        ->color('primary'),
                                    Infolists\Components\TextEntry::make('notes')
                                        ->label('Заметка / Комментарий')
                                        ->placeholder('Нет комментариев'),
                                    Infolists\Components\TextEntry::make('tutor_report_summary')
                                        ->label('Итог урока')
                                        ->placeholder('Отчёт ещё не заполнен'),
                                    Infolists\Components\TextEntry::make('tutor_report_focus')
                                        ->label('Фокус и найденные пробелы')
                                        ->placeholder('Нет данных'),
                                    Infolists\Components\TextEntry::make('tutor_next_step')
                                        ->label('Следующий шаг')
                                        ->placeholder('Нет данных'),
                                    Infolists\Components\TextEntry::make('tutor_homework_summary')
                                        ->label('Домашняя работа')
                                        ->placeholder('Не назначена'),
                                ]),
                        ])->columnSpan(2),

                        Infolists\Components\Group::make([
                            Infolists\Components\Section::make('Участники')
                                ->schema([
                                    Infolists\Components\TextEntry::make('tutor.name')
                                        ->label('Репетитор')
                                        ->icon('heroicon-m-academic-cap'),
                                    Infolists\Components\TextEntry::make('student.name')
                                        ->label('Ученик')
                                        ->icon('heroicon-m-user'),
                                    Infolists\Components\TextEntry::make('parent.name')
                                        ->label('Родитель')
                                        ->visible(fn ($record) => $record->parent_id !== null)
                                        ->icon('heroicon-m-user-group'),
                                ]),

                            Infolists\Components\Section::make('Финансы')
                                ->schema([
                                    Infolists\Components\TextEntry::make('price')
                                        ->label('Стоимость урока')
                                        ->formatStateUsing(fn ($state) => BynMoneyFormatter::format((string) $state)),
                                    Infolists\Components\TextEntry::make('package_label')
                                        ->label('Тип оплаты'),
                                    Infolists\Components\TextEntry::make('platform_commission')
                                        ->label('Комиссия платформы')
                                        ->formatStateUsing(fn ($state) => BynMoneyFormatter::format((string) $state))
                                        ->visible(fn () => auth()->user()?->role === 'admin'),
                                    Infolists\Components\TextEntry::make('net_amount')
                                        ->label('К выплате репетитору')
                                        ->formatStateUsing(fn ($state) => BynMoneyFormatter::format((string) $state))
                                        ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'tutor'], true)),
                                ]),

                            Infolists\Components\Section::make('Отзыв')
                                ->visible(fn (Lesson $record) => $record->review()->exists())
                                ->schema([
                                    Infolists\Components\TextEntry::make('review.rating')
                                        ->label('Оценка')
                                        ->icon('heroicon-m-star')
                                        ->color('warning'),
                                    Infolists\Components\TextEntry::make('review.feedback')
                                        ->label('Текст отзыва')
                                        ->prose(),
                                ]),
                        ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_time')
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('tutor.name')
                    ->label('Репетитор')
                    ->searchable()
                    ->description(fn (Lesson $record): string => $record->package_label)
                    ->toggleable(isToggledHiddenByDefault: auth()->user()?->role === 'tutor'),
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Ученик')
                    ->searchable()
                    ->description(fn (Lesson $record): string => $record->parent?->name ? 'Родитель: ' . $record->parent->name : 'Самостоятельная запись')
                    ->toggleable(isToggledHiddenByDefault: auth()->user()?->role !== 'tutor'),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Начало')
                    ->formatStateUsing(fn ($state): string => $state->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i'))
                    ->description(fn (Lesson $record): string => $record->start_time->isFuture()
                        ? $record->start_time->diffForHumans()
                        : 'Урок уже прошёл')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Конец')
                    ->formatStateUsing(fn ($state): string => $state->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn (mixed $state) => BynMoneyFormatter::format((string) $state)),
                Tables\Columns\TextColumn::make('package_label')
                    ->label('Пакет')
                    ->badge()
                    ->color(fn (Lesson $record): string => $record->package_code === 'single' ? 'gray' : 'primary')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('package_total')
                    ->label('Сумма пакета')
                    ->formatStateUsing(fn (mixed $state) => $state === null ? null : BynMoneyFormatter::format((string) $state))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('transaction.net_amount')
                    ->label('К выплате')
                    ->formatStateUsing(fn (mixed $state) => $state === null ? null : BynMoneyFormatter::format((string) $state))
                    ->placeholder('0.00')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => self::statusColor($state))
                    ->formatStateUsing(fn (string $state): string => self::statusLabel($state)),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->badge()
                    ->color(fn (string $state): string => self::paymentColor($state))
                    ->formatStateUsing(fn (string $state): string => self::paymentLabel($state)),
                Tables\Columns\TextColumn::make('meeting_link')
                    ->label('Встреча')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Ссылка есть' : 'Ждёт ссылки')
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'success' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tutor_reported_at')
                    ->label('Отчёт')
                    ->formatStateUsing(fn ($state): string => $state ? 'Заполнен' : 'Не заполнен')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: auth()->user()?->role === 'admin'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        Lesson::STATUS_PENDING => 'Ожидает',
                        Lesson::STATUS_CONFIRMED => 'Подтвержден',
                        Lesson::STATUS_COMPLETED => 'Завершен',
                        Lesson::STATUS_CANCELLED => 'Отменен',
                        Lesson::STATUS_NO_SHOW => 'Неявка',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Оплата')
                    ->options([
                        Lesson::PAYMENT_UNPAID => 'Не оплачено',
                        Lesson::PAYMENT_PAID => 'Оплачено',
                        Lesson::PAYMENT_REFUNDED => 'Возврат',
                        Lesson::PAYMENT_PARTIALLY_REFUNDED => 'Частичный возврат',
                    ]),
                Filter::make('today')
                    ->label('Сегодня')
                    ->query(fn (Builder $query): Builder => $query->whereDate('start_time', now(config('booking.display_timezone'))->toDateString())),
                Filter::make('needs_report')
                    ->label('Без отчёта')
                    ->visible(fn (): bool => auth()->user()?->role === 'tutor')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_COMPLETED])
                        ->where('payment_status', Lesson::PAYMENT_PAID)
                        ->whereNull('tutor_reported_at')),
                Filter::make('date_range')
                    ->label('Период')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('С'),
                        Forms\Components\DatePicker::make('date_until')->label('По'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_time', '>=', $date))
                            ->when($data['date_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_time', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Подробнее')
                    ->button()
                    ->color('gray'),
                Tables\Actions\Action::make('open_meeting')
                    ->label('Войти')
                    ->icon('heroicon-o-video-camera')
                    ->color('primary')
                    ->url(fn (Lesson $record): ?string => $record->meeting_link)
                    ->openUrlInNewTab()
                    ->visible(fn (Lesson $record): bool => filled($record->meeting_link)
                        && $record->payment_status === Lesson::PAYMENT_PAID
                        && in_array($record->status, [Lesson::STATUS_CONFIRMED, Lesson::STATUS_COMPLETED], true)),
                Tables\Actions\Action::make('confirm')
                    ->label('Подтвердить')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Lesson $record): bool => in_array(auth()->user()?->role, ['admin', 'tutor'], true) && $record->status === Lesson::STATUS_PENDING && $record->payment_status === Lesson::PAYMENT_PAID)
                    ->requiresConfirmation()
                    ->action(function (Lesson $record): void {
                        $record->update(['status' => Lesson::STATUS_CONFIRMED]);
                        $record->student?->notify(new LessonConfirmedNotification($record->fresh()));
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Завершить')
                    ->color('success')
                    ->icon('heroicon-o-flag')
                    ->visible(fn (Lesson $record): bool => in_array(auth()->user()?->role, ['admin', 'tutor'], true)
                        && $record->status === Lesson::STATUS_CONFIRMED
                        && $record->payment_status === Lesson::PAYMENT_PAID
                        && $record->end_time->isPast())
                    ->requiresConfirmation()
                    ->action(function (Lesson $record): void {
                        $record->update(['status' => Lesson::STATUS_COMPLETED]);
                        app(PaymentService::class)->settleCompletedLesson($record->fresh());

                        Notification::make()
                            ->title('Урок завершён')
                            ->body('Оплата переведена в расчёты по завершённому уроку.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('pay')
                    ->label('Оплатить урок')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn (Lesson $record): bool => in_array(auth()->user()?->role, ['student', 'parent'], true) && $record->payment_status === Lesson::PAYMENT_UNPAID)
                    ->url(fn (Lesson $record): string => route('checkout.show', $record)),
                Tables\Actions\Action::make('meeting_link')
                    ->label('Ссылка на встречу')
                    ->icon('heroicon-o-video-camera')
                    ->visible(fn (): bool => in_array(auth()->user()?->role, ['admin', 'tutor'], true))
                    ->fillForm(fn (Lesson $record): array => ['meeting_link' => $record->meeting_link])
                    ->form([
                        Forms\Components\TextInput::make('meeting_link')
                            ->label('Ссылка')
                            ->url()
                            ->required(),
                    ])
                    ->action(fn (Lesson $record, array $data): bool => $record->update(['meeting_link' => $data['meeting_link']])),
                Tables\Actions\Action::make('post_lesson_report')
                    ->label('Отчёт после урока')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->visible(fn (Lesson $record): bool => auth()->user()?->role === 'tutor'
                        && in_array($record->status, [Lesson::STATUS_CONFIRMED, Lesson::STATUS_COMPLETED], true)
                        && $record->payment_status === Lesson::PAYMENT_PAID)
                    ->fillForm(fn (Lesson $record): array => [
                        'summary' => $record->tutor_report_summary,
                        'focus' => $record->tutor_report_focus,
                        'next_step' => $record->tutor_next_step,
                        'homework' => $record->tutor_homework_summary,
                        'score_estimate' => $record->tutor_report_score,
                    ])
                    ->form([
                        Forms\Components\Textarea::make('summary')
                            ->label('Что прошли на уроке')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000),
                        Forms\Components\Textarea::make('focus')
                            ->label('Какие пробелы или сильные стороны заметили')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000),
                        Forms\Components\Textarea::make('next_step')
                            ->label('Что делать дальше')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000),
                        Forms\Components\Textarea::make('homework')
                            ->label('Домашняя работа')
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\TextInput::make('score_estimate')
                            ->label('Оценка текущего уровня')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('Например, 58'),
                    ])
                    ->action(function (Lesson $record, array $data): void {
                        app(PostLessonReportService::class)->saveReport(
                            lesson: $record,
                            actorId: (int) auth()->id(),
                            summary: (string) $data['summary'],
                            focus: (string) $data['focus'],
                            nextStep: (string) $data['next_step'],
                            homework: $data['homework'] ?? null,
                            scoreEstimate: isset($data['score_estimate']) && $data['score_estimate'] !== ''
                                ? (int) $data['score_estimate']
                                : null,
                        );

                        Notification::make()
                            ->title('Отчёт сохранён')
                            ->body('Прогресс и домашняя работа обновлены.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('review')
                    ->label('Оценить')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (Lesson $record): bool => in_array(auth()->user()?->role, ['student', 'parent'], true)
                        && $record->status === Lesson::STATUS_COMPLETED
                        && $record->payment_status === Lesson::PAYMENT_PAID
                        && $record->review === null)
                    ->form([
                        Forms\Components\Select::make('rating')
                            ->label('Оценка')
                            ->options([
                                5 => '5 — отлично',
                                4 => '4 — хорошо',
                                3 => '3 — нормально',
                                2 => '2 — есть проблемы',
                                1 => '1 — плохо',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('feedback')
                            ->label('Комментарий')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (Lesson $record, array $data): void {
                        LessonReview::query()->create([
                            'lesson_id' => $record->id,
                            'reviewer_id' => (int) auth()->id(),
                            'rating' => (int) $data['rating'],
                            'feedback' => filled($data['feedback'] ?? null) ? trim((string) $data['feedback']) : null,
                            'is_public' => (int) $data['rating'] >= 4,
                            'submitted_at' => now('UTC'),
                        ]);

                        Notification::make()
                            ->title('Спасибо, отзыв сохранён')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (Lesson $record): bool {
                        $user = auth()->user();

                        if (in_array($user?->role, ['admin', 'tutor'], true)) {
                            return in_array($record->status, [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED], true);
                        }

                        if (in_array($user?->role, ['student', 'parent'], true)) {
                            return in_array($record->status, [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED], true)
                                && $record->start_time->isAfter(now('UTC')->addDay());
                        }

                        return false;
                    })
                    ->requiresConfirmation()
                    ->action(function (Lesson $record): void {
                        if ($record->payment_status === Lesson::PAYMENT_PAID) {
                            app(PaymentService::class)->refundLessonPayment($record, 'lesson_cancelled');

                            Notification::make()
                                ->title('Урок отменен, возврат оформлен')
                                ->success()
                                ->send();

                            return;
                        }

                        $record->update(['status' => Lesson::STATUS_CANCELLED]);
                        $record->student?->notify(new LessonCancelledNotification($record->fresh()));
                        $record->tutor?->notify(new LessonCancelledNotification($record->fresh()));

                        Notification::make()
                            ->title('Урок отменен')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reschedule')
                    ->label('Перенести')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (): bool => in_array(auth()->user()?->role, ['student', 'parent'], true))
                    ->requiresConfirmation()
                    ->modalDescription('Функция переноса появится в следующей итерации MVP.')
                    ->action(fn (): null => null),
                Tables\Actions\Action::make('chat')
                    ->label('Написать')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(function (Lesson $record): string {
                        $conversation = app(ChatService::class)->getOrCreateConversation(
                            tutorId: $record->tutor_id,
                            studentId: $record->student_id,
                            lessonId: $record->id,
                        );

                        return "/admin/messages?conversation={$conversation->id}";
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['tutor', 'student', 'parent', 'transaction', 'review']);
        $user = auth()->user();

        if ($user?->role === 'admin') {
            return $query;
        }

        if ($user?->role === 'tutor') {
            return $query->where('tutor_id', $user->id);
        }

        if ($user?->role === 'parent') {
            return $query->where(function (Builder $builder) use ($user): Builder {
                return $builder
                    ->where('student_id', $user->id)
                    ->orWhere('parent_id', $user->id);
            });
        }

        return $query->where('student_id', $user?->id);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            Lesson::STATUS_PENDING => 'Ожидает',
            Lesson::STATUS_CONFIRMED => 'Подтверждён',
            Lesson::STATUS_CANCELLED => 'Отменён',
            Lesson::STATUS_COMPLETED => 'Завершён',
            Lesson::STATUS_NO_SHOW => 'Неявка',
            default => $status,
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            Lesson::STATUS_PENDING => 'warning',
            Lesson::STATUS_CONFIRMED => 'success',
            Lesson::STATUS_CANCELLED => 'danger',
            Lesson::STATUS_COMPLETED => 'gray',
            Lesson::STATUS_NO_SHOW => 'danger',
            default => 'gray',
        };
    }

    public static function paymentLabel(string $status): string
    {
        return match ($status) {
            Lesson::PAYMENT_UNPAID => 'Не оплачено',
            Lesson::PAYMENT_PAID => 'Оплачено',
            Lesson::PAYMENT_REFUNDED => 'Возврат',
            Lesson::PAYMENT_PARTIALLY_REFUNDED => 'Частичный возврат',
            default => $status,
        };
    }

    public static function paymentColor(string $status): string
    {
        return match ($status) {
            Lesson::PAYMENT_PAID => 'success',
            Lesson::PAYMENT_UNPAID => 'warning',
            Lesson::PAYMENT_REFUNDED, Lesson::PAYMENT_PARTIALLY_REFUNDED => 'danger',
            default => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'view' => Pages\ViewLesson::route('/{record}'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId === 'site-admin') {
            return 'Уроки';
        }

        return match (auth()->user()?->role) {
            'tutor' => 'Моё расписание',
            'student', 'parent' => 'Мои уроки',
            default => 'Уроки',
        };
    }

    public static function getNavigationGroup(): ?string
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId === 'site-admin') {
            return 'Операции';
        }

        return auth()->user()?->role === 'tutor'
            ? 'Организация'
            : 'Обучение';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId === 'site-admin') {
            return $user?->role === 'admin';
        }

        return in_array($user?->role, ['tutor', 'student', 'parent'], true);
    }
}
