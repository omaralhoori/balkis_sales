<?php

namespace App\Filament\Resources\Accommodations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccommodationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                TextColumn::make('destination.name')->label('الوجهة')->searchable(),
                TextColumn::make('type')->label('النوع')->searchable(),
                TextColumn::make('default_buying_price')->label('سعر الشراء الافتراضي')->money('usd')->sortable(),
                TextColumn::make('default_selling_price')->label('سعر البيع الافتراضي')->money('usd')->sortable(),
            ])
            ->filters([
                SelectFilter::make('stars')
                    ->label('عدد النجوم')
                    ->options([
                        1 => '★ (1 نجمة)',
                        2 => '★★ (2 نجمة)',
                        3 => '★★★ (3 نجمات)',
                        4 => '★★★★ (4 نجمات)',
                        5 => '★★★★★ (5 نجمات)',
                    ]),
                SelectFilter::make('type')
                    ->label('نوع السكن')
                    ->options([
                        'فندق' => 'فندق',
                        'شقق فندقية' => 'شقق فندقية',
                        'كوخ' => 'كوخ',
                        'فيلا' => 'فيلا',
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
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
