<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Support\BynMoneyFormatter;
use App\Services\Payment\PaymentService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('lesson.id')
                    ->label('Урок'),
                Tables\Columns\TextColumn::make('lesson.tutor.name')
                    ->label('Репетитор')
                    ->toggleable(isToggledHiddenByDefault: auth()->user()?->role === 'tutor'),
                Tables\Columns\TextColumn::make('lesson.student.name')
                    ->label('Ученик')
                    ->toggleable(isToggledHiddenByDefault: auth()->user()?->role !== 'tutor'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Сумма')
                    ->formatStateUsing(fn (mixed $state) => BynMoneyFormatter::format((string) $state)),
                Tables\Columns\TextColumn::make('platform_commission')
                    ->label('Комиссия')
                    ->formatStateUsing(fn (mixed $state) => BynMoneyFormatter::format((string) $state))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('acquiring_fee')
                    ->label('Эквайринг')
                    ->formatStateUsing(fn (mixed $state) => BynMoneyFormatter::format((string) $state))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('К выплате')
                    ->formatStateUsing(fn (mixed $state) => BynMoneyFormatter::format((string) $state)),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Transaction::STATUS_SUCCESS => 'success',
                        Transaction::STATUS_PENDING => 'warning',
                        Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED => 'danger',
                        Transaction::STATUS_PARTIALLY_REFUNDED => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Transaction::STATUS_PENDING => 'Ожидает',
                        Transaction::STATUS_SUCCESS => 'Успешно',
                        Transaction::STATUS_FAILED => 'Ошибка',
                        Transaction::STATUS_REFUNDED => 'Возврат',
                        Transaction::STATUS_PARTIALLY_REFUNDED => 'Частичный возврат',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Оплачено')
                    ->formatStateUsing(fn ($state): string => $state?->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') ?? '—'),
            ])
            ->actions([])
            ->headerActions([
                Tables\Actions\Action::make('request_payout')
                    ->label('Запросить выплату')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->visible(fn (): bool => auth()->user()?->role === 'tutor')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        app(PaymentService::class)->requestPayout(auth()->id());

                        Notification::make()
                            ->title('Заявка на выплату зафиксирована')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['lesson.tutor', 'lesson.student', 'user']);
        $user = auth()->user();

        if ($user?->role === 'admin') {
            return $query;
        }

        if ($user?->role === 'tutor') {
            return $query->whereHas('lesson', fn (Builder $builder): Builder => $builder->where('tutor_id', $user->id));
        }

        return $query->where('user_id', $user?->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        if ($panelId === 'site-admin') {
            return 'Транзакции';
        }

        return match (auth()->user()?->role) {
            'tutor' => 'Транзакции',
            'student', 'parent' => 'Мои оплаты',
            default => 'Транзакции',
        };
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Финансы';
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
