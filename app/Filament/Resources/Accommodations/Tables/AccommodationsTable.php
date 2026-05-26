<?php

namespace App\Filament\Resources\Accommodations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccommodationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable(),
                TextColumn::make('destination.name')->label('الوجهة')->searchable(),
                TextColumn::make('type')->label('النوع')->searchable(),
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
