<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TutorProfile;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TutorVerificationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly TutorProfile $tutorProfile) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (! empty($notifiable->email) && filter_var($notifiable->email, FILTER_VALIDATE_EMAIL)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        $catalogUrl = route('tutors.show', $this->tutorProfile->id);

        return FilamentNotification::make()
            ->title('🎉 Ваша анкета одобрена!')
            ->body('Поздравляем! Ваша анкета репетитора успешно проверена. Вам присвоен бейдж «✓ Диплом проверен», и ваш профиль опубликован в каталоге.')
            ->icon('heroicon-o-check-badge')
            ->iconColor('success')
            ->actions([
                Action::make('view_catalog')
                    ->label('Посмотреть в каталоге')
                    ->url($catalogUrl)
                    ->openUrlInNewTab()
                    ->button(),
                Action::make('availability')
                    ->label('Календарь занятий')
                    ->url('/admin/tutor-availability-page')
                    ->color('gray'),
            ])
            ->getDatabaseMessage() + [
                'tutor_profile_id' => $this->tutorProfile->id,
                'type' => 'tutor_verification_approved',
                'url' => $catalogUrl,
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $catalogUrl = route('tutors.show', $this->tutorProfile->id);

        return (new MailMessage)
            ->subject('🎉 Ваша анкета репетитора на Edusfera одобрена!')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Отличные новости! Ваша анкета репетитора успешно проверена администратором платформы Edusfera.')
            ->line('Вам присвоен статус «✓ Диплом проверен», и ваша анкета опубликована в открытом каталоге.')
            ->line('Теперь ученики и их родители могут находить вас, бронировать пробные уроки и оплачивать занятия онлайн.')
            ->action('Посмотреть анкету в каталоге', $catalogUrl)
            ->line('Рекомендуем проверить и настроить ваше расписание свободных часов в календаре кабинета, чтобы ученики могли записаться на удобное время.')
            ->salutation('С уважением, команда Edusfera');
    }
}
