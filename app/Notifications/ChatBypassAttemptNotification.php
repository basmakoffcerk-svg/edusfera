<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChatBypassAttemptNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $tutor,
        private readonly Conversation $conversation,
        private readonly Message $message,
        private readonly int $attemptsCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Попытка обхода платформы')
            ->body("Репетитор {$this->tutor->name} пытался передать контакты. Попыток: {$this->attemptsCount}.")
            ->icon('heroicon-o-shield-exclamation')
            ->iconColor('danger')
            ->actions([
                \Filament\Notifications\Actions\Action::make('open_chat')
                    ->label('Открыть чат')
                    ->url("/admin/messages?conversation={$this->conversation->id}"),
            ])
            ->getDatabaseMessage() + [
                'conversation_id' => $this->conversation->id,
                'message_id' => $this->message->id,
                'tutor_id' => $this->tutor->id,
                'attempts_count' => $this->attemptsCount,
                'url' => "/admin/messages?conversation={$this->conversation->id}",
            ];
    }
}
