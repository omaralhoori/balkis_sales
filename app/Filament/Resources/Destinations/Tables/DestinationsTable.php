<?php

namespace App\Filament\Resources\Destinations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DestinationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('اسم الوجهة')->searchable(),
                TextColumn::make('type')
                    ->label('نوع الوجهة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'accommodation' => 'إقامة',
                        'tour' => 'رحلات',
                        'both' => 'كلاهما',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accommodation' => 'info',
                        'tour' => 'warning',
                        'both' => 'success',
                        default => 'gray',
                    }),
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
