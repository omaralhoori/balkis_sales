<?php

namespace App\Filament\Resources\Destinations\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;

class DestinationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('اسم الوجهة')->required(),
            ]);
    }
}
