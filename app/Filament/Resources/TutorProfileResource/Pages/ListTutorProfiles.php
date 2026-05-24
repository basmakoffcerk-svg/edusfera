<?php

namespace App\Filament\Resources\TutorProfileResource\Pages;

use App\Filament\Resources\TutorProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTutorProfiles extends ListRecords
{
    protected static string $resource = TutorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Начать настройку профиля')
                ->visible(function (): bool {
                    $user = auth()->user();

                    return $user?->role === 'tutor' && ! $user->tutorProfile()->exists();
                }),
        ];
    }
}
