<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Lesson;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LessonLowRatingNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Lesson $lesson,
        private readonly User $student,
        private readonly int $rating,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Низкая оценка после урока')
            ->body("Ученик {$this->student->name} поставил {$this->rating}/5 за урок #{$this->lesson->id}. Проверьте ситуацию.")
            ->icon('heroicon-o-exclamation-circle')
            ->iconColor('warning')
            ->actions([
                Action::make('open_requests')
                    ->label('Открыть заявки')
                    ->url('/admin/lesson-requests'),
            ])
            ->getDatabaseMessage() + [
                'lesson_id' => $this->lesson->id,
                'rating' => $this->rating,
                'student_id' => $this->student->id,
                'url' => '/admin/lesson-requests',
            ];
    }
}
