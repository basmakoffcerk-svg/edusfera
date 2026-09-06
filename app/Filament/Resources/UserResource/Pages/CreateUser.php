<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['new_password'])) {
            $data['password'] = Hash::make($data['new_password']);
        }
        unset($data['new_password']);

        return $data;
    }
}
