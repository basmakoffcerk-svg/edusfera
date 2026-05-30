<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lesson;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Lesson $lesson)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Урок подтвержден')
            ->body('Репетитор подтвердил урок #' . $this->lesson->id . '.')
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->getDatabaseMessage() + [
                'lesson_id' => $this->lesson->id,
                'url' => '/admin/lessons',
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $start = $this->lesson->start_time
            ->clone()
            ->setTimezone(config('booking.display_timezone'))
            ->format('d.m.Y H:i');

        $message = (new MailMessage())
            ->subject('Урок подтвержден')
            ->greeting('Здравствуйте, ' . $notifiable->name . '!')
            ->line('Репетитор подтвердил вашу запись.')
            ->line('Дата и время: ' . $start . ' (Минск)');

        if ($this->lesson->meeting_link) {
            $message->line('Ссылка на встречу: ' . $this->lesson->meeting_link);
        }

        return $message
            ->action('Открыть кабинет', url('/admin/lessons'))
            ->line('Если потребуется отмена или перенос, сделайте это заранее.');
    }
}
