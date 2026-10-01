<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lesson;
use Filament\Notifications\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonBookedStudentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Lesson $lesson) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $start = $this->lesson->start_time
            ?->clone()
            ->setTimezone((string) config('booking.display_timezone', 'Europe/Minsk'))
            ->format('d.m.Y H:i');

        $tutorName = $this->lesson->tutor?->name ?? 'Репетитор';
        $title = 'Заявка на урок оформлена';
        $body = "Вы забронировали урок с {$tutorName} на {$start} (Минск). Репетитор скоро подтвердит заявку.";
        $url = '/admin/lessons';

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->icon('heroicon-o-calendar-days')
            ->iconColor('success')
            ->actions([
                FilamentAction::make('view')
                    ->label('Мои занятия')
                    ->url($url),
            ])
            ->getDatabaseMessage() + [
                'title' => $title,
                'body' => $body,
                'lesson_id' => $this->lesson->id,
                'url' => $url,
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $start = $this->lesson->start_time
            ?->clone()
            ->setTimezone((string) config('booking.display_timezone', 'Europe/Minsk'))
            ->format('d.m.Y H:i');

        $tutorName = $this->lesson->tutor?->name ?? 'Репетитор';

        return (new MailMessage)
            ->subject('Вы записались на урок — Edusfera')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Ваша заявка на урок успешно создана.')
            ->line('Преподаватель: '.$tutorName)
            ->line('Дата и время: '.$start.' (Минск)')
            ->action('Открыть личный кабинет', url('/admin/lessons'))
            ->line('После подтверждения репетитором вы получите отдельное уведомление.');
    }
}
