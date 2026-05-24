<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LessonRequestResource\Pages;
use App\Models\Lesson;
use App\Notifications\LessonCancelledNotification;
use App\Notifications\LessonConfirmedNotification;
use App\Services\ChatService;
use App\Services\Payment\PaymentService;
use App\Support\BynMoneyFormatter;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonRequestResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?int $navigationSort = 2;

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return LessonResource::infolist($infolist);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Ученик')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Дата и время')
                    ->formatStateUsing(fn ($state): string => $state->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i')),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена урока')
                    ->formatStateUsing(fn (mixed $state) => BynMoneyFormatter::format((string) $state)),
                Tables\Columns\TextColumn::make('package_label')
                    ->label('Пакет')
                    ->badge()
                    ->color(fn (Lesson $record): string => $record->package_code === 'single' ? 'gray' : 'primary'),
                Tables\Columns\TextColumn::make('package_total')
                    ->label('Сумма пакета')
                    ->formatStateUsing(fn (mixed $state) => $state === null ? null : BynMoneyFormatter::format((string) $state))
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Оплата')
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
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус заявки')
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
                        Lesson::STATUS_PENDING => 'Новая',
                        Lesson::STATUS_CONFIRMED => 'Подтверждена',
                        Lesson::STATUS_CANCELLED => 'Отменена',
                        Lesson::STATUS_COMPLETED => 'Завершена',
                        Lesson::STATUS_NO_SHOW => 'Неявка',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Комментарий')
                    ->limit(70)
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Детали')
                    ->button()
                    ->color('gray'),
                Tables\Actions\Action::make('confirm')
                    ->label('Подтвердить')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Lesson $record): bool => $record->status === Lesson::STATUS_PENDING && $record->payment_status === Lesson::PAYMENT_PAID)
                    ->requiresConfirmation()
                    ->action(function (Lesson $record): void {
                        $record->update(['status' => Lesson::STATUS_CONFIRMED]);
                        $record->student?->notify(new LessonConfirmedNotification($record->fresh()));
                    }),
                Tables\Actions\Action::make('meeting_link')
                    ->label('Ссылка')
                    ->icon('heroicon-o-video-camera')
                    ->fillForm(fn (Lesson $record): array => ['meeting_link' => $record->meeting_link])
                    ->form([
                        Forms\Components\TextInput::make('meeting_link')
                            ->label('Ссылка на встречу')
                            ->url()
                            ->required(),
                    ])
                    ->action(fn (Lesson $record, array $data): bool => $record->update(['meeting_link' => $data['meeting_link']])),
                Tables\Actions\Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Lesson $record): bool => in_array($record->status, [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED], true))
                    ->requiresConfirmation()
                    ->action(function (Lesson $record): void {
                        if ($record->payment_status === Lesson::PAYMENT_PAID) {
                            app(PaymentService::class)->refundLessonPayment($record, 'lesson_cancelled');

                            return;
                        }

                        $record->update(['status' => Lesson::STATUS_CANCELLED]);
                        $record->student?->notify(new LessonCancelledNotification($record->fresh()));
                        $record->tutor?->notify(new LessonCancelledNotification($record->fresh()));
                    }),
                Tables\Actions\Action::make('chat')
                    ->label('Чат')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessonRequests::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['student', 'tutor']);
        $user = auth()->user();

        if ($user?->role === 'admin') {
            return $query;
        }

        return $query->where('tutor_id', $user?->id);
    }

    public static function getNavigationLabel(): string
    {
        return Filament::getCurrentPanel()?->getId() === 'site-admin'
            ? 'Заявки на уроки'
            : 'Заявки';
    }

    public static function getNavigationGroup(): ?string
    {
        return Filament::getCurrentPanel()?->getId() === 'site-admin'
            ? 'Операции'
            : 'Организация';
    }

    public static function getModelLabel(): string
    {
        return 'Заявка';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Заявки';
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId === 'site-admin') {
            return $user?->role === 'admin';
        }

        return $user?->role === 'tutor';
    }
}
