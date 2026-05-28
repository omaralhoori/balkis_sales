<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        $record = $schema->getRecord();
        $isNumericSetting = $record && in_array($record->key, ['voucher_footer_bottom', 'voucher_footer_height']);

        return $schema
            ->components([
                TextInput::make('key')
                    ->label('مفتاح الإعداد (Key)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null),

                $isNumericSetting
                    ? TextInput::make('value')
                        ->label('القيمة (Value)')
                        ->helperText('الارتفاع بالملليمتر (مثلاً: 10 أو 35)')
                        ->numeric()
                        ->required()
                    : RichEditor::make('value')
                        ->label('القيمة (Value)')
                        ->columnSpanFull(),
            ]);
    }
}
