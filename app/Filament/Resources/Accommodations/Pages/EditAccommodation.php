<?php

namespace App\Filament\Resources\Accommodations\Pages;

use App\Filament\Resources\Accommodations\AccommodationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccommodation extends EditRecord
{
    protected static string $resource = AccommodationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
