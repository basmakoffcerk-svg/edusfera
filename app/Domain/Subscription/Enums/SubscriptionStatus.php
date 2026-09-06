<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum SubscriptionStatus: string
{
    case TRIAL = 'trial';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELED = 'canceled';
    case EXPIRED = 'expired';

    public function title(): string
    {
        return match ($this) {
            self::TRIAL => 'Пробный период (Бесплатно)',
            self::ACTIVE => 'Активна',
            self::PAST_DUE => 'Ожидает оплаты (Grace period)',
            self::CANCELED => 'Отменена',
            self::EXPIRED => 'Истекла',
        };
    }

    public function isOperational(): bool
    {
        return in_array($this, [self::TRIAL, self::ACTIVE, self::PAST_DUE], true);
    }
}
