<?php

declare(strict_types=1);

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use App\Models\Lesson;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLesson extends ViewRecord
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('open_classroom')
                ->label('Войти в класс')
                ->icon('heroicon-o-video-camera')
                ->color('primary')
                ->url(fn (Lesson $record): string => route('classroom.show', $record))
                ->visible(fn (Lesson $record): bool => $record->payment_status === Lesson::PAYMENT_PAID
                    && in_array($record->status, [Lesson::STATUS_CONFIRMED, Lesson::STATUS_COMPLETED], true)),
        ];
    }
}
