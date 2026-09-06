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

    public function isOperational(?\DateTimeInterface $gracePeriodEndsAt = null): bool
    {
        if ($this === self::TRIAL || $this === self::ACTIVE) {
            return true;
        }

        if ($this === self::PAST_DUE) {
            return $gracePeriodEndsAt === null || $gracePeriodEndsAt > now();
        }

        return false;
    }
}
