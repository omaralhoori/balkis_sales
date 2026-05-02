<?php

namespace App\Filament\Resources\Accommodations\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;

class AccommodationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('اسم السكن')->required(),
                Select::make('type')->label('نوع السكن')->options([
                    'فندق' => 'فندق',
                    'شقق فندقية' => 'شقق فندقية',
                    'كوخ' => 'كوخ',
                ])->required(),
                TextInput::make('default_buying_price')->label('سعر الشراء الافتراضي')->numeric()->required(),
                TextInput::make('default_selling_price')->label('سعر البيع الافتراضي')->numeric()->required(),
                FileUpload::make('images')->label('الصور')->multiple()->image()->directory('accommodations'),
            ]);
    }
}
