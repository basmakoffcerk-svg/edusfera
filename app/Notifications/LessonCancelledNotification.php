<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lesson;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonCancelledNotification extends Notification implements ShouldQueue
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
            ->title('Урок отменен')
            ->body('Статус урока #' . $this->lesson->id . ' изменился на "Отменен".')
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->getDatabaseMessage() + [
                'lesson_id' => $this->lesson->id,
                'url' => '/admin/lessons',
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Урок отменен')
            ->greeting('Здравствуйте, ' . $notifiable->name . '!')
            ->line('Урок #' . $this->lesson->id . ' был отменен.')
            ->action('Открыть кабинет', url('/admin/lessons'));
    }
}
