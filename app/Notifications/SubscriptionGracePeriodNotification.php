<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Domain\Subscription\Models\Subscription;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionGracePeriodNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Subscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Не удалось списать оплату за подписку')
            ->body('Активирован льготный период (3 дня). Пожалуйста, обновите платёжные данные.')
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('warning')
            ->getDatabaseMessage() + [
                'subscription_id' => $this->subscription->id,
                'url' => '/admin/tutor-subscription-page',
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Не удалось продлить подписку Edusfera')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Не удалось автоматически списать оплату за вашу подписку Edusfera.')
            ->line('Вам предоставлен 3-дневный льготный период для обновления карты или оплаты по ЕРИП.')
            ->action('Управление подпиской', url('/admin/tutor-subscription-page'));
    }
}
