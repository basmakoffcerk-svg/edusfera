<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TutorProfile;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TutorVerificationSubmittedNotification extends Notification implements ShouldQueue
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
        return FilamentNotification::make()
            ->title('Анкета отправлена на проверку')
            ->body('Мы получили вашу анкету и документы. Технический администратор проверит данные (обычно в течение нескольких часов), после чего профиль появится в каталоге.')
            ->icon('heroicon-o-clock')
            ->iconColor('info')
            ->getDatabaseMessage() + [
                'tutor_profile_id' => $this->tutorProfile->id,
                'type' => 'tutor_verification_submitted',
                'url' => '/admin',
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ваша анкета репетитора принята на проверку')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Ваша анкета репетитора и подтверждающие документы успешно приняты на модерацию.')
            ->line('Технический администратор Edusfera проверит данные в ближайшее время.')
            ->line('Как только анкета будет одобрена, вы получите уведомление, и ваш профиль сразу станет виден в каталоге.')
            ->action('Перейти в личный кабинет', url('/admin'))
            ->salutation('С уважением, команда Edusfera');
    }
}
