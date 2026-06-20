<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Transaction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Transaction $transaction) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Оплата прошла успешно')
            ->body('Урок #'.$this->transaction->lesson_id.' успешно оплачен на сумму '.$this->transaction->amount.'.')
            ->icon('heroicon-o-credit-card')
            ->iconColor('success')
            ->getDatabaseMessage() + [
                'lesson_id' => $this->transaction->lesson_id,
                'transaction_id' => $this->transaction->id,
                'url' => '/admin/transactions',
            ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Оплата прошла успешно')
            ->greeting('Здравствуйте, '.$notifiable->name.'!')
            ->line('Оплата за урок #'.$this->transaction->lesson_id.' успешно принята.')
            ->line('Сумма: '.$this->transaction->amount)
            ->action('Открыть оплаты', url('/admin/transactions'));
    }
}
