<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('المفتاح')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'voucher_additional_details' => 'تفاصيل إضافية أسفل الفاتورة (HTML)',
                        'voucher_footer_bottom' => 'موضع بداية التذييل من الأسفل (ملليمتر)',
                        'voucher_footer_height' => 'ارتفاع الهامش السفلي لمنع التداخل (ملليمتر)',
                        default => $state,
                    }),
                TextColumn::make('value')->label('القيمة')->limit(50),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
