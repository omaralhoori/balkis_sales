<?php

namespace App\Filament\Resources\Cars\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class CarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('car_type')->label('نوع السيارة')->required(),
                TextInput::make('default_buying_price')->label('سعر الشراء الافتراضي')->numeric()->required(),
                TextInput::make('default_selling_price')->label('سعر البيع الافتراضي')->numeric()->required(),
                FileUpload::make('images')->label('الصور')->multiple()->image()->directory('cars'),
            ]);
    }
}
