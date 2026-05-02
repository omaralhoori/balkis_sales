<?php

namespace App\Filament\Resources\Cars\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class CarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('car_type')->label('نوع السيارة')->searchable(),
                TextColumn::make('default_buying_price')->label('سعر الشراء الافتراضي')->money('usd'),
                TextColumn::make('default_selling_price')->label('سعر البيع الافتراضي')->money('usd'),
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
