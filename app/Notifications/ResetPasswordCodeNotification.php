<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Код восстановления пароля: {$this->code} — Edusfera")
            ->greeting('Здравствуйте'.(! empty($notifiable->name) ? ', '.$notifiable->name : '').'!')
            ->line('Вы запросили восстановление пароля для входа в платформу Edusfera.')
            ->line("Ваш одноразовый код подтверждения: **{$this->code}**")
            ->line('Код действителен в течение 15 минут.')
            ->line('Если вы не запрашивали сброс пароля, проигнорируйте это письмо — ваш пароль останется прежним.');
    }
}
