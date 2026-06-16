<?php

namespace App\Filament\Resources\Destinations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DestinationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('اسم الوجهة')->required(),
                Select::make('type')
                    ->label('نوع الوجهة')
                    ->options([
                        'accommodation' => 'إقامة',
                        'tour' => 'رحلات',
                        'both' => 'كلاهما',
                    ])
                    ->default('both')
                    ->required(),
            ]);
    }
}
