<?php

namespace App\Filament\Resources\Accommodations\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccommodationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('اسم السكن')->required(),
                Select::make('destination_id')
                    ->label('الوجهة')
                    ->relationship('destination', 'name')
                    ->required(),
                Select::make('type')->label('نوع السكن')->options([
                    'فندق' => 'فندق',
                    'شقق فندقية' => 'شقق فندقية',
                    'كوخ' => 'كوخ',
                    'فيلا' => 'فيلا',
                ])->required(),
                TextInput::make('default_buying_price')->label('سعر الشراء الافتراضي')->numeric()->required(),
                TextInput::make('default_selling_price')->label('سعر البيع الافتراضي')->numeric()->required(),
                TextInput::make('video_url')
                    ->label('رابط الفيديو')
                    ->url()
                    ->placeholder('https://example.com/video')
                    ->nullable(),
                FileUpload::make('images')->label('الصور')->multiple()->image()->directory('accommodations'),
            ]);
    }
}
