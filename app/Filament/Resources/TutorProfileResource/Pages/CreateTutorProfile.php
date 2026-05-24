<?php

namespace App\Filament\Resources\TutorProfileResource\Pages;

use App\Filament\Resources\TutorProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTutorProfile extends CreateRecord
{
    protected static string $resource = TutorProfileResource::class;

    public function getTitle(): string
    {
        return 'Настройка профиля репетитора';
    }

    public function getSubheading(): ?string
    {
        return 'Заполните профиль, чтобы получить первые заявки от родителей и учеников.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['is_verified'] = auth()->user()?->role === 'admin'
            ? (bool) ($data['is_verified'] ?? false)
            : false;
        $data['verification_status'] = $data['is_verified'] ? 'approved' : 'pending';
        $data['verification_submitted_at'] = now();
        $data['onboarding_completed_at'] = now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
