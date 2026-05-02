<?php

namespace App\Filament\Resources\Accommodations\Pages;

use App\Filament\Resources\Accommodations\AccommodationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccommodations extends ListRecords
{
    protected static string $resource = AccommodationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
