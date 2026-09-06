<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Enums;

enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';

    public function title(): string
    {
        return match ($this) {
            self::PENDING => 'Ожидает оплаты',
            self::PAID => 'Оплачен',
            self::EXPIRED => 'Просрочен',
            self::CANCELED => 'Аннулирован',
        };
    }
}
