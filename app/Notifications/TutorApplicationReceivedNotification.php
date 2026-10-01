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

class TutorApplicationReceivedNotification extends Notification implements ShouldQueue
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
        $tutorName = $this->tutorProfile->user?->name ?? 'Новый репетитор';
        $editUrl = '/site-admin/tutor-profiles/'.$this->tutorProfile->id.'/edit';

        $subjects = is_array($this->tutorProfile->subjects)
            ? implode(', ', $this->tutorProfile->subjects)
            : ($this->tutorProfile->subjects ?? '');

        $body = "Репетитор {$tutorName} отправил(а) анкету и документы на модерацию.";
        if ($subjects) {
            $body .= " Предметы: {$subjects}.";
        }

        return FilamentNotification::make()
            ->title('Новая анкета репетитора на проверку')
            ->body($body)
            ->icon('heroicon-o-document-magnifying-glass')
            ->iconColor('warning')
            ->actions([
                Action::make('review')
                    ->label('Проверить анкету')
                    ->url($editUrl)
                    ->button(),
            ])
            ->getDatabaseMessage() + [
                'tutor_profile_id' => $this->tutorProfile->id,
                'type' => 'tutor_application_received',
                'url' => $editUrl,
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tutorName = $this->tutorProfile->user?->name ?? 'Новый репетитор';
        $editUrl = url('/site-admin/tutor-profiles/'.$this->tutorProfile->id.'/edit');

        return (new MailMessage)
            ->subject('Новая анкета репетитора на проверку ('.$tutorName.')')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line("Репетитор {$tutorName} отправил(а) анкету и скан диплома на модерацию.")
            ->action('Проверить анкету в панели управления', $editUrl)
            ->line('Пожалуйста, проверьте документы и примите решение об одобрении профиля.');
    }
}
