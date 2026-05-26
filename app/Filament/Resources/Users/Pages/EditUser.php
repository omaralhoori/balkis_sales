<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function fillFormDataForTesting(array $state = [], ?string $schemaStatePath = null): void
    {
        foreach (Arr::dot($state) as $statePath => $value) {
            data_set($this, $statePath, $value);
        }
    }
}
