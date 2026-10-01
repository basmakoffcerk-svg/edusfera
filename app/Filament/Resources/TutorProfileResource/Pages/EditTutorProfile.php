<?php

namespace App\Filament\Resources\TutorProfileResource\Pages;

use App\Filament\Resources\TutorProfileResource;
use App\Models\TutorProfile;
use App\Services\TutorVerificationService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTutorProfile extends EditRecord
{
    protected static string $resource = TutorProfileResource::class;

    public function getTitle(): string
    {
        return (bool) auth()->user()?->isAdmin()
            ? 'Модерация анкеты репетитора'
            : 'Редактирование профиля репетитора';
    }

    public function getSubheading(): ?string
    {
        return (bool) auth()->user()?->isAdmin()
            ? 'Проверьте анкету, документы и примите решение по публикации в каталоге.'
            : null;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if ($user?->isTutor()) {
            $data['is_verified'] = false;
            $data['verification_status'] = 'pending';
            $data['verification_submitted_at'] = now();
            $data['onboarding_completed_at'] = now();
        }

        if ($user?->isAdmin()) {
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
        $user = auth()->user();

        if ($user?->isTutor()) {
            app(TutorVerificationService::class)->submitForReview($profile);

            Notification::make()
                ->title('Анкета отправлена на проверку')
                ->body('Технический администратор проверит данные.')
                ->info()
                ->send();

            return;
        }

        if ($user?->isAdmin()) {
            if ($profile->verification_status === 'approved') {
                app(TutorVerificationService::class)->approve($profile, $user);
            } elseif ($profile->verification_status === 'rejected') {
                app(TutorVerificationService::class)->reject($profile, $user);
            }

            Notification::make()
                ->title(match ($profile->verification_status) {
                    'approved' => 'Анкета одобрена и опубликована в каталоге',
                    'rejected' => 'Анкета отклонена',
                    default => 'Анкета сохранена',
                })
                ->success()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        return static::getResource()::getUrl('index', panel: $panelId);
    }

    protected function getFormActions(): array
    {
        if (auth()->user()?->isAdmin()) {
            return parent::getFormActions();
        }

        return [];
    }

    protected function getHeaderActions(): array
    {
        if (! auth()->user()?->isAdmin()) {
            return [];
        }

        return [
            Actions\Action::make('approve')
                ->label('Одобрить анкету')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Одобрить анкету репетитора?')
                ->modalDescription('Профиль получит статус «✓ Диплом проверен», будет опубликован в каталоге, а репетитор получит уведомление.')
                ->modalSubmitActionLabel('Да, одобрить')
                ->action(function (): void {
                    /** @var TutorProfile $profile */
                    $profile = $this->record;

                    app(TutorVerificationService::class)->approve($profile, auth()->user());

                    Notification::make()
                        ->title('Анкета одобрена и опубликована')
                        ->body('Репетитор получил уведомление.')
                        ->success()
                        ->send();

                    $this->redirect($this->getRedirectUrl());
                }),
            Actions\Action::make('reject')
                ->label('Отклонить')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->modalHeading('Отклонить анкету репетитора?')
                ->modalDescription('Профиль будет снят с публикации. Репетитору будет отправлено уведомление.')
                ->modalSubmitActionLabel('Отклонить анкету')
                ->form([
                    Textarea::make('reason')
                        ->label('Причина отклонения / комментарий для репетитора')
                        ->placeholder('Например: Пожалуйста, обновите скан диплома...')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    /** @var TutorProfile $profile */
                    $profile = $this->record;

                    app(TutorVerificationService::class)->reject(
                        $profile,
                        auth()->user(),
                        $data['reason'] ?? null
                    );

                    Notification::make()
                        ->title('Анкета отклонена')
                        ->body('Репетитор получил уведомление.')
                        ->danger()
                        ->send();

                    $this->redirect($this->getRedirectUrl());
                }),
        ];
    }
}
