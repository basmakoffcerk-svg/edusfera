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

class TutorVerificationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TutorProfile $tutorProfile,
        private readonly ?string $reason = null,
    ) {}

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
        $editUrl = '/admin/tutor-profiles/'.$this->tutorProfile->id.'/edit';
        $reasonText = $this->reason
            ? ' Причина: '.$this->reason
            : ' Пожалуйста, проверьте качество фото, скан диплома и полноту описания опыта преподавания.';

        return FilamentNotification::make()
            ->title('Анкета требует доработки')
            ->body('Модератор проверил вашу анкету, но для публикации требуются уточнения.'.$reasonText)
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('danger')
            ->actions([
                Action::make('edit_profile')
                    ->label('Редактировать анкету')
                    ->url($editUrl)
                    ->button(),
            ])
            ->getDatabaseMessage() + [
                'tutor_profile_id' => $this->tutorProfile->id,
                'type' => 'tutor_verification_rejected',
                'url' => $editUrl,
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $editUrl = url('/admin/tutor-profiles/'.$this->tutorProfile->id.'/edit');

        $mail = (new MailMessage)
            ->subject('Анкета репетитора на Edusfera требует доработки')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Модератор проверил вашу анкету репетитора на платформе Edusfera.')
            ->line('К сожалению, в текущем виде анкета не может быть опубликована в каталоге.');

        if ($this->reason) {
            $mail->line('Комментарий модератора: '.$this->reason);
        } else {
            $mail->line('Пожалуйста, проверьте чёткость скана диплома/сертификата, портретное фото и подробность описания вашего опыта.');
        }

        return $mail
            ->action('Исправить анкету', $editUrl)
            ->line('После внесения изменений анкета будет повторно проверена модератором.')
            ->salutation('С уважением, служба поддержки Edusfera');
    }
}
