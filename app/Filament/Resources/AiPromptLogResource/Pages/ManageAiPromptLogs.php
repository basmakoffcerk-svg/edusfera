<?php

declare(strict_types=1);

namespace App\Filament\Resources\AiPromptLogResource\Pages;

use App\Filament\Resources\AiPromptLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAiPromptLogs extends ManageRecords
{
    protected static string $resource = AiPromptLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
