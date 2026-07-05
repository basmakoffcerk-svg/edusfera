<?php

declare(strict_types=1);

namespace App\Filament\Resources\DisputeResource\Pages;

use App\Filament\Resources\DisputeResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDisputes extends ManageRecords
{
    protected static string $resource = DisputeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
