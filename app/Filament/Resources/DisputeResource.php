<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DisputeResource\Pages;
use App\Models\Dispute;
use App\Models\Lesson;
use App\Services\Payment\PaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('lesson_id')
                    ->label('Урок')
                    ->relationship('lesson', 'id')
                    ->disabled()
                    ->required(),
                Forms\Components\Select::make('initiator_id')
                    ->label('Инициатор спора')
                    ->relationship('initiator', 'name')
                    ->disabled()
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->label('Статус спора')
                    ->disabled(),
                Forms\Components\Textarea::make('reason')
                    ->label('Причина спора')
                    ->rows(4)
                    ->columnSpanFull()
                    ->disabled(),
                Forms\Components\Textarea::make('resolution_notes')
                    ->label('Решение арбитража / Заметки поддержки')
                    ->rows(4)
                    ->columnSpanFull()
                    ->placeholder('Опишите причину принятия данного решения...'),
                Forms\Components\Select::make('resolved_by')
                    ->label('Кем решен')
                    ->relationship('resolver', 'name')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('resolved_at')
                    ->label('Дата решения')
                    ->disabled(),
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
                Tables\Columns\TextColumn::make('lesson.id')
                    ->label('Урок')
                    ->description(fn (Dispute $record): string => 
                        'Репетитор: ' . ($record->lesson->tutor->name ?? '—') . 
                        ' | Ученик: ' . ($record->lesson->student->name ?? '—')
                    ),
                Tables\Columns\TextColumn::make('initiator.name')
                    ->label('Инициатор'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Причина')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Dispute::STATUS_OPEN => 'warning',
                        Dispute::STATUS_RESOLVED_REFUNDED => 'success',
                        Dispute::STATUS_RESOLVED_PAYOUT => 'info',
                        Dispute::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Dispute::STATUS_OPEN => 'Открыт',
                        Dispute::STATUS_RESOLVED_REFUNDED => 'Возврат ученику',
                        Dispute::STATUS_RESOLVED_PAYOUT => 'Выплата репетитору',
                        Dispute::STATUS_REJECTED => 'Отклонен',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('refund_to_student')
                    ->label('Вернуть деньги ученику')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Dispute $record): bool => $record->status === Dispute::STATUS_OPEN)
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки арбитража')
                            ->required()
                            ->placeholder('Причина возврата (будет сохранена в транзакции)...'),
                    ])
                    ->action(function (Dispute $record, array $data): void {
                        try {
                            $paymentService = app(PaymentService::class);
                            $paymentService->refundLessonPayment($record->lesson, $data['notes']);

                            $record->update([
                                'status' => Dispute::STATUS_RESOLVED_REFUNDED,
                                'resolved_by' => auth()->id(),
                                'resolved_at' => now(),
                                'resolution_notes' => $data['notes'],
                            ]);

                            Notification::make()
                                ->title('Спор разрешен')
                                ->body('Средства успешно возвращены ученику (bePaid / баланс).')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Ошибка проведения возврата')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('payout_to_tutor')
                    ->label('Выплатить репетитору')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Dispute $record): bool => $record->status === Dispute::STATUS_OPEN)
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки арбитража')
                            ->required()
                            ->placeholder('Причина выплаты репетитору...'),
                    ])
                    ->action(function (Dispute $record, array $data): void {
                        try {
                            $paymentService = app(PaymentService::class);
                            $paymentService->settleCompletedLesson($record->lesson);

                            $record->update([
                                'status' => Dispute::STATUS_RESOLVED_PAYOUT,
                                'resolved_by' => auth()->id(),
                                'resolved_at' => now(),
                                'resolution_notes' => $data['notes'],
                            ]);

                            Notification::make()
                                ->title('Спор разрешен')
                                ->body('Средства успешно выплачены на баланс репетитора.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Ошибка проведения выплаты')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reject_dispute')
                    ->label('Отклонить спор')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Dispute $record): bool => $record->status === Dispute::STATUS_OPEN)
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Заметки арбитража')
                            ->required()
                            ->placeholder('Причина отклонения спора...'),
                    ])
                    ->action(function (Dispute $record, array $data): void {
                        $record->update([
                            'status' => Dispute::STATUS_REJECTED,
                            'resolved_by' => auth()->id(),
                            'resolved_at' => now(),
                            'resolution_notes' => $data['notes'],
                        ]);

                        Notification::make()
                            ->title('Спор отклонен')
                            ->body('Спор закрыт без изменения финансовых транзакций.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDisputes::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Диспетчер споров (Арбитраж)';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Финансы';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === \App\Enums\UserRole::Admin;
    }
}
