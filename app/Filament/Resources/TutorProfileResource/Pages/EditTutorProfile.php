<?php

namespace App\Filament\Resources\TutorProfileResource\Pages;

use App\Filament\Resources\TutorProfileResource;
use App\Models\TutorProfile;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTutorProfile extends EditRecord
{
    protected static string $resource = TutorProfileResource::class;

    public function getTitle(): string
    {
        return auth()->user()?->role === 'admin'
            ? 'Модерация анкеты репетитора'
            : 'Редактирование профиля репетитора';
    }

    public function getSubheading(): ?string
    {
        return auth()->user()?->role === 'admin'
            ? 'Проверьте анкету, документы и примите решение по публикации в каталоге.'
            : null;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->user()?->role === 'tutor') {
            $data['is_verified'] = false;
            $data['verification_status'] = 'pending';
            $data['verification_submitted_at'] = now();
            $data['onboarding_completed_at'] = now();
        }

        if (auth()->user()?->role === 'admin') {
            $status = $data['verification_status'] ?? 'pending';
            $data['verification_status'] = $status;
            $data['is_verified'] = $status === 'approved';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var TutorProfile $profile */
        $profile = $this->record;

        if (auth()->user()?->role !== 'admin') {
            return;
        }

        $profile->user?->update([
            'is_verified' => $profile->is_verified,
        ]);

        Notification::make()
            ->title(match ($profile->verification_status) {
                'approved' => 'Анкета одобрена и опубликована в каталоге',
                'rejected' => 'Анкета отклонена',
                default => 'Анкета возвращена в статус проверки',
            })
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        return static::getResource()::getUrl('index', panel: $panelId);
    }

    protected function getHeaderActions(): array
    {
        if (auth()->user()?->role !== 'admin') {
            return [];
        }

        return [
            Actions\Action::make('approve')
                ->label('Одобрить')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var TutorProfile $profile */
                    $profile = $this->record;

                    $profile->update([
                        'verification_status' => 'approved',
                        'is_verified' => true,
                    ]);

                    $profile->user?->update([
                        'is_verified' => true,
                    ]);

                    Notification::make()
                        ->title('Анкета одобрена и опубликована')
                        ->success()
                        ->send();

                    $this->redirect($this->getRedirectUrl());
                }),
            Actions\Action::make('reject')
                ->label('Отклонить')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var TutorProfile $profile */
                    $profile = $this->record;

                    $profile->update([
                        'verification_status' => 'rejected',
                        'is_verified' => false,
                    ]);

                    $profile->user?->update([
                        'is_verified' => false,
                    ]);

                    Notification::make()
                        ->title('Анкета отклонена')
                        ->danger()
                        ->send();

                    $this->redirect($this->getRedirectUrl());
                }),
        ];
    }
}
