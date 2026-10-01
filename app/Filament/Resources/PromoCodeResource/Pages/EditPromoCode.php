<?php

declare(strict_types=1);

namespace App\Filament\Resources\PromoCodeResource\Pages;

use App\Filament\Resources\PromoCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromoCode extends EditRecord
{
    protected static string $resource = PromoCodeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['discount_type'] ?? '', ['free_period', 'lifetime'], true)) {
            $data['discount_value'] = 0.00;
        }

        if (($data['discount_type'] ?? '') === 'lifetime') {
            $data['subscription_period'] = 'lifetime';
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
