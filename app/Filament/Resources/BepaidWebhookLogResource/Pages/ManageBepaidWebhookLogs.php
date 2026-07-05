<?php

declare(strict_types=1);

namespace App\Filament\Resources\BepaidWebhookLogResource\Pages;

use App\Filament\Resources\BepaidWebhookLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageBepaidWebhookLogs extends ManageRecords
{
    protected static string $resource = BepaidWebhookLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
