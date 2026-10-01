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

class LessonBookedTutorNotification extends Notification implements ShouldQueue
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

        $studentName = $this->lesson->student?->name ?? 'Ученик';
        $packageLabel = match ($this->lesson->package_code) {
            'pack_4' => ' (Пакет 4 занятия)',
            'pack_8' => ' (Пакет 8 занятий)',
            default => '',
        };

        $title = 'Новая бронь занятия!';
        $body = "Ученик {$studentName} забронировал урок на {$start}{$packageLabel}.";
        $url = '/admin/lesson-requests';

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->icon('heroicon-o-calendar-days')
            ->iconColor('success')
            ->actions([
                FilamentAction::make('view')
                    ->label('Посмотреть заявку')
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

        $studentName = $this->lesson->student?->name ?? 'Ученик';
        $studentPhone = $this->lesson->student?->phone;
        $notes = $this->lesson->notes;
        $packageLabel = match ($this->lesson->package_code) {
            'pack_4' => 'Пакет 4 занятия',
            'pack_8' => 'Пакет 8 занятий',
            default => '1 занятие',
        };

        $mail = (new MailMessage)
            ->subject('Новая бронь занятия на Edusfera')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line("Появилась новая бронь занятия от ученика: {$studentName}.")
            ->line('Дата и время: '.$start.' (Минск)')
            ->line('Формат: '.$packageLabel);

        if ($studentPhone) {
            $mail->line('Телефон ученика: '.$studentPhone);
        }

        if ($notes) {
            $mail->line('Комментарий к уроку: «'.$notes.'»');
        }

        return $mail
            ->action('Открыть заявку в кабинете', url('/admin/lesson-requests'))
            ->line('Пожалуйста, перейдите в панель управления для подтверждения бронирования.');
    }
}
