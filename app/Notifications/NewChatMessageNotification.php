<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Conversation;
use App\Models\Message;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChatMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Conversation $conversation,
        private readonly Message $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Новое сообщение')
            ->body(mb_strimwidth($this->message->message, 0, 120, '...'))
            ->icon('heroicon-o-chat-bubble-left-right')
            ->iconColor('success')
            ->actions([
                \Filament\Notifications\Actions\Action::make('open_chat')
                    ->label('Открыть чат')
                    ->url("/admin/messages?conversation={$this->conversation->id}"),
            ])
            ->getDatabaseMessage() + [
                'conversation_id' => $this->conversation->id,
                'lesson_id' => $this->conversation->lesson_id,
                'sender_id' => $this->message->sender_id,
                'url' => "/admin/messages?conversation={$this->conversation->id}",
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Новое сообщение в Edusfera')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Вам пришло новое сообщение в чате.')
            ->line(mb_strimwidth($this->message->message, 0, 200, '...'))
            ->action('Открыть чат', url("/admin/messages?conversation={$this->conversation->id}"));
    }
}
