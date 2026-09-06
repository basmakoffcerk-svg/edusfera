<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum SubscriptionPlan: string
{
    case START = 'start';
    case PRO = 'pro';
    case BASIC = 'basic';
    case PREMIUM = 'premium';

    public function title(): string
    {
        return match ($this) {
            self::START, self::BASIC => 'Старт',
            self::PRO, self::PREMIUM => 'Pro',
        };
    }

    public function monthlyPriceKopecks(): int
    {
        return match ($this) {
            self::START, self::BASIC => 2900,
            self::PRO, self::PREMIUM => 5900,
        };
    }

    public function monthlyPriceByn(): int
    {
        return (int) ($this->monthlyPriceKopecks() / 100);
    }

    public function yearlyMonthlyEquivalent(): float
    {
        return round($this->yearlyPriceByn() / 12, 2);
    }

    /**
     * 2 months free on yearly subscription (pay for 10 months).
     */
    public function yearlyPriceKopecks(): int
    {
        return match ($this) {
            self::START, self::BASIC => 29000,
            self::PRO, self::PREMIUM => 59000,
        };
    }

    public function yearlyPriceByn(): int
    {
        return (int) ($this->yearlyPriceKopecks() / 100);
    }

    public function maxResponsesPerMonth(): ?int
    {
        return null;
    }

    public function features(): array
    {
        return match ($this) {
            self::START, self::BASIC => [
                'Виртуальный класс (SFU)',
                'Интерактивная доска (Workspace)',
                'CRM и расписание уроков',
                'Неограниченно учеников',
                'Персональная ссылка для записи',
            ],
            self::PRO, self::PREMIUM => [
                'Все возможности тарифа «Старт»',
                'ИИ-диагностика знаний (тесты РИКЗ)',
                'ИИ-помощник (конспекты, ДЗ, тесты)',
                'Авто-НПД (чеки МНС РБ)',
                'Персональный брендинг комнат',
            ],
        };
    }

    public function allowsVideoCalls(): bool
    {
        return true;
    }

    public function allowsCalendarSync(): bool
    {
        return true;
    }

    public function allowsAnalytics(): bool
    {
        return match ($this) {
            self::START, self::BASIC => false,
            self::PRO, self::PREMIUM => true,
        };
    }

    public function searchRankWeight(): int
    {
        return match ($this) {
            self::START, self::BASIC => 1,
            self::PRO, self::PREMIUM => 5,
        };
    }
}
