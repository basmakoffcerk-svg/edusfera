<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonRequestResource\Pages;

use App\Filament\Resources\LessonRequestResource;
use App\Models\Lesson;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLessonRequests extends ListRecords
{
    protected static string $resource = LessonRequestResource::class;

    public function getTitle(): string
    {
        return 'Заявки';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'new' => Tab::make('Новые')
                ->badge(fn () => $this->baseLessonsQuery()->where('status', Lesson::STATUS_PENDING)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', Lesson::STATUS_PENDING)),
            'paid' => Tab::make('Оплаченные')
                ->badge(fn () => $this->baseLessonsQuery()->where('payment_status', Lesson::PAYMENT_PAID)->count())
                ->modifyQueryUsing(fn ($query) => $query
                    ->where('payment_status', Lesson::PAYMENT_PAID)),
            'active' => Tab::make('В работе')
                ->modifyQueryUsing(fn ($query) => $query->whereIn('status', [Lesson::STATUS_CONFIRMED, Lesson::STATUS_COMPLETED])),
            'all' => Tab::make('Все'),
        ];
    }

    private function baseLessonsQuery(): Builder
    {
        return LessonRequestResource::getEloquentQuery();
    }
}
