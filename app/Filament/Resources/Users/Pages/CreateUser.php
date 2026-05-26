<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function fillFormDataForTesting(array $state = [], ?string $schemaStatePath = null): void
    {
        foreach (Arr::dot($state) as $statePath => $value) {
            data_set($this, $statePath, $value);
        }
    }
}
