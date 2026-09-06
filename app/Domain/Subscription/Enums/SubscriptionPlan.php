<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum SubscriptionPlan: string
{
    case BASIC = 'basic';
    case PRO = 'pro';
    case PREMIUM = 'premium';

    public function title(): string
    {
        return match ($this) {
            self::BASIC => 'Basic',
            self::PRO => 'Pro',
            self::PREMIUM => 'Premium',
        };
    }

    public function monthlyPriceKopecks(): int
    {
        return match ($this) {
            self::BASIC => 2000,
            self::PRO => 4000,
            self::PREMIUM => 6000,
        };
    }

    public function monthlyPriceByn(): int
    {
        return $this->monthlyPriceKopecks() / 100;
    }

    /**
     * 20% discount on yearly subscription (pay for 10 months).
     */
    public function yearlyPriceKopecks(): int
    {
        return match ($this) {
            self::BASIC => 19200, // 16 BYN/mo * 12 = 192 BYN
            self::PRO => 38400,   // 32 BYN/mo * 12 = 384 BYN
            self::PREMIUM => 57600, // 48 BYN/mo * 12 = 576 BYN
        };
    }

    public function yearlyPriceByn(): int
    {
        return $this->yearlyPriceKopecks() / 100;
    }

    public function maxResponsesPerMonth(): ?int
    {
        return match ($this) {
            self::BASIC => 0,
            self::PRO => 10,
            self::PREMIUM => null, // unlimited
        };
    }

    public function allowsVideoCalls(): bool
    {
        return match ($this) {
            self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function allowsCalendarSync(): bool
    {
        return match ($this) {
            self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function allowsAnalytics(): bool
    {
        return match ($this) {
            self::BASIC => false,
            self::PRO, self::PREMIUM => true,
        };
    }

    public function searchRankWeight(): int
    {
        return match ($this) {
            self::BASIC => 1,
            self::PRO => 5,
            self::PREMIUM => 10,
        };
    }
}
