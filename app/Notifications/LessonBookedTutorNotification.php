<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonBookedTutorNotification extends Notification implements ShouldQueue
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
            'title' => 'Новая заявка на урок',
            'body' => 'Новая заявка от ' . $this->lesson->student->name . ' по уроку #' . $this->lesson->id . '.',
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
            ->subject('Новая заявка на урок')
            ->greeting('Здравствуйте, ' . $notifiable->name . '!')
            ->line('Появилась новая заявка на урок от ' . $this->lesson->student->name . '.')
            ->line('Дата и время: ' . $start . ' (Минск)')
            ->action('Открыть расписание', url('/admin/lessons'))
            ->line('Проверьте детали и подтвердите занятие в кабинете.');
    }
}
