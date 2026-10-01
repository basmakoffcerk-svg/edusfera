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
                ->label(fn (Lesson $record): string => (! config('classroom.enabled', false) && ! empty($record->meeting_link)) ? 'Подключиться к уроку' : 'Войти в класс')
                ->icon('heroicon-o-video-camera')
                ->color('primary')
                ->url(function (Lesson $record): string {
                    if (! config('classroom.enabled', false) && ! empty($record->meeting_link) && str_starts_with($record->meeting_link, 'http')) {
                        return $record->meeting_link;
                    }

                    return route('classroom.show', $record);
                })
                ->openUrlInNewTab(fn (Lesson $record): bool => ! config('classroom.enabled', false) && ! empty($record->meeting_link) && str_starts_with($record->meeting_link, 'http'))
                ->visible(fn (Lesson $record): bool => in_array($record->status, [Lesson::STATUS_CONFIRMED, Lesson::STATUS_COMPLETED], true)),
        ];
    }
}
