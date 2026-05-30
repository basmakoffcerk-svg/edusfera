<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonBookedStudentNotification extends Notification implements ShouldQueue
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
        return [
            'title' => 'Вы записались на урок',
            'body' => 'Заявка на урок #' . $this->lesson->id . ' создана.',
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

        return (new MailMessage())
            ->subject('Вы записались на урок')
            ->greeting('Здравствуйте, ' . $notifiable->name . '!')
            ->line('Ваша заявка на урок создана.')
            ->line('Репетитор: ' . $this->lesson->tutor->name)
            ->line('Дата и время: ' . $start . ' (Минск)')
            ->action('Открыть кабинет', url('/admin/lessons'))
            ->line('После подтверждения репетитором вы получите отдельное уведомление.');
    }
}
