<?php

namespace App\Filament\Resources\Accommodations\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class AccommodationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('الاسم')->required(),
                Select::make('type')->label('النوع')->options([
                    'فندق' => 'فندق', 
                    'شقة فندقية' => 'شقة فندقية', 
                    'كوخ' => 'كوخ'
                ])->default('فندق')->required(),
                TextInput::make('default_buying_price')->label('سعر الشراء الافتراضي')->numeric()->default(0)->required(),
                TextInput::make('default_selling_price')->label('سعر البيع الافتراضي')->numeric()->default(0)->required(),
            ]);
    }
}
