<?php

declare(strict_types=1);

namespace App\Filament\Resources\PromoCodeResource\Pages;

use App\Filament\Resources\PromoCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePromoCode extends CreateRecord
{
    protected static string $resource = PromoCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        if (in_array($data['discount_type'] ?? '', ['free_period', 'lifetime'], true)) {
            $data['discount_value'] = 0.00;
        }

        if (($data['discount_type'] ?? '') === 'lifetime') {
            $data['subscription_period'] = 'lifetime';
        }

        return $data;
    }
}
