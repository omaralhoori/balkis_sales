<?php

namespace App\Filament\Resources\Tours\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TourForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('اسم الجولة')->required(),
                Select::make('destination_id')
                    ->label('الوجهة')
                    ->relationship('destination', 'name')
                    ->required(),
                Select::make('type')->label('نوع الجولة')->options([
                    'خاص VIP' => 'خاص VIP',
                    'مجموعة Group' => 'مجموعة Group',
                ])->default('خاص VIP')->required(),
                Textarea::make('short_description')->label('وصف قصير')->columnSpanFull(),
                TextInput::make('external_link')->label('رابط خارجي')->url(),
                TextInput::make('default_buying_price')->label('سعر الشراء الافتراضي')->numeric()->default(0)->required(),
                TextInput::make('default_selling_price')->label('سعر البيع الافتراضي')->numeric()->default(0)->required(),
            ]);
    }
}
