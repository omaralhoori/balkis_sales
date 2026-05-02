<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')->label('مفتاح الإعداد (Key)')->required()->unique(ignoreRecord: true),
                RichEditor::make('value')->label('القيمة (Value)')->columnSpanFull(),
            ]);
    }
}
