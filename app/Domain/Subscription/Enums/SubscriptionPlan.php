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
            self::START, self::BASIC => 'Стандарт',
            self::PRO => 'Pro',
            self::PREMIUM => 'Премиум',
        };
    }

    public function label(): string
    {
        return $this->title();
    }

    public function getLabel(): string
    {
        return $this->title();
    }

    public function monthlyPriceKopecks(): int
    {
        return match ($this) {
            self::START, self::BASIC => 2000,
            self::PRO => 4000,
            self::PREMIUM => 6000,
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
     * 20% discount on yearly subscription (10 months price for 12 months access).
     */
    public function yearlyPriceKopecks(): int
    {
        return match ($this) {
            self::START, self::BASIC => 19200,
            self::PRO => 38400,
            self::PREMIUM => 57600,
        };
    }

    public function yearlyPriceByn(): int
    {
        return (int) ($this->yearlyPriceKopecks() / 100);
    }

    public function trialDays(): int
    {
        return match ($this) {
            self::START, self::BASIC => 5,
            self::PRO => 14,
            self::PREMIUM => 28,
        };
    }

    public function maxResponsesPerMonth(): ?int
    {
        return match ($this) {
            self::START, self::BASIC => 0,
            self::PRO => 10,
            self::PREMIUM => null,
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::START, self::BASIC => 'Для старта',
            self::PRO => '⭐ Хит продаж',
            self::PREMIUM => '👑 Топ-эксперт',
        };
    }

    public function subtitle(): string
    {
        return match ($this) {
            self::START, self::BASIC => 'Для работы со своими учениками и комфортного ведения частных уроков',
            self::PRO => 'Для стабильного потока учеников, ИИ-автоматизации и авто-чеков НПД',
            self::PREMIUM => 'Максимальный доход: Топ-позиции в каталоге, свой брендинг и безлимит',
        };
    }

    public function features(): array
    {
        return match ($this) {
            self::START, self::BASIC => [
                'Виртуальный класс (SFU) и интерактивная доска',
                'CRM и расписание уроков',
                'Неограниченно своих учеников и персональная ссылка на запись',
                'Базовое облачное хранилище (1 ГБ)',
                'Работа со своими клиентами (0 откликов на бирже)',
            ],
            self::PRO => [
                'Все возможности тарифа «Стандарт»',
                'ИИ-диагностика знаний (тесты РИКЗ)',
                'ИИ-помощник (конспекты, ДЗ, тесты)',
                'Авто-генерация чеков НПД (МНС РБ)',
                'Уроки без лимита по времени + 15 ГБ хранилища',
                'SMS и Telegram автонапоминания ученикам',
                'Отклики на входящие заявки (до 10 в месяц)',
            ],
            self::PREMIUM => [
                'Все возможности тарифов «Стандарт» и «Pro»',
                '👑 Золотой бейдж «Топ-эксперт» и Топ-позиции в каталоге',
                'Видео-визитка прямо в поисковой выдаче каталога',
                'БЕЗЛИМИТНЫЕ отклики на заявки и автоподбор учеников',
                'Персональный брендинг комнат (логотип, цвета, свой URL)',
                'Двусторонняя авто-синхронизация с Google Calendar',
                'Безлимитное облачное хранилище (100 ГБ) и запись уроков',
                'Глубокая финансовая и когортная аналитика (LTV, когорты)',
                'Мгновенный вывод средств (Instant Payout) без задержек',
                'Персональный менеджер и приоритетная VIP-поддержка 24/7',
            ],
        };
    }

    public function storageLimitGb(): int
    {
        return match ($this) {
            self::START, self::BASIC => 1,
            self::PRO => 15,
            self::PREMIUM => 100,
        };
    }

    public function maxLessonDurationMinutes(): int
    {
        return match ($this) {
            self::START, self::BASIC => 60,
            self::PRO => 120,
            self::PREMIUM => 240,
        };
    }

    public function allowsVideoCalls(): bool
    {
        return true;
    }

    public function allowsCalendarSync(): bool
    {
        return match ($this) {
            self::START, self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function allowsBranding(): bool
    {
        return match ($this) {
            self::START, self::BASIC => false,
            self::PRO, self::PREMIUM => true,
        };
    }

    public function allowsVideoIntro(): bool
    {
        return match ($this) {
            self::START, self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function allowsInstantPayout(): bool
    {
        return match ($this) {
            self::START, self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function allowsAnalytics(): bool
    {
        return match ($this) {
            self::START, self::BASIC => false,
            self::PRO, self::PREMIUM => true,
        };
    }

    public function allowsDeepAnalytics(): bool
    {
        return match ($this) {
            self::START, self::BASIC, self::PRO => false,
            self::PREMIUM => true,
        };
    }

    public function searchRankWeight(): int
    {
        return match ($this) {
            self::START, self::BASIC => 1,
            self::PRO => 5,
            self::PREMIUM => 10,
        };
    }
}
