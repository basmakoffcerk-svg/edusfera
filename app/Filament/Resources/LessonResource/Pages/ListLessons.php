<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use App\Filament\Resources\LessonResource\Widgets\LessonOverviewWidget;
use App\Models\Lesson;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    public function getTitle(): string
    {
        return LessonResource::getNavigationLabel();
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Требуют внимания')
                ->icon('heroicon-m-bolt')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                    $query
                        ->where(function (Builder $query): void {
                            $query
                                ->where('payment_status', Lesson::PAYMENT_UNPAID)
                                ->where('status', '!=', Lesson::STATUS_CANCELLED);
                        })
                        ->orWhere(function (Builder $query): void {
                            $query
                                ->where('status', Lesson::STATUS_PENDING)
                                ->where('payment_status', Lesson::PAYMENT_PAID);
                        })
                        ->orWhere(function (Builder $query): void {
                            $query
                                ->where('status', Lesson::STATUS_CONFIRMED)
                                ->where('start_time', '<=', now('UTC')->addDay())
                                ->where('end_time', '>=', now('UTC'));
                        });
                })),
            'upcoming' => Tab::make('Ближайшие')
                ->icon('heroicon-m-calendar-days')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('start_time', '>=', now('UTC'))
                    ->whereIn('status', [Lesson::STATUS_PENDING, Lesson::STATUS_CONFIRMED])),
            'completed' => Tab::make('Завершённые')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', Lesson::STATUS_COMPLETED)),
            'all' => Tab::make('Все')
                ->icon('heroicon-m-queue-list'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'active';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LessonOverviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        $role = auth()->user()?->role;

        return [
            Actions\Action::make('book_lesson')
                ->label('Найти репетитора')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->url('/tutors')
                ->visible(fn (): bool => in_array($role, ['student', 'parent'], true)),
            Actions\Action::make('availability')
                ->label('Открыть расписание')
                ->icon('heroicon-o-calendar')
                ->url(fn (): string => route('filament.admin.pages.tutor-availability-page'))
                ->visible(fn (): bool => $role === 'tutor'),
            Actions\Action::make('payments')
                ->label($role === 'tutor' ? 'Финансы' : 'Оплаты')
                ->icon('heroicon-o-banknotes')
                ->url('/admin/transactions')
                ->visible(fn (): bool => in_array($role, ['tutor', 'student', 'parent'], true)),
        ];
    }
}
