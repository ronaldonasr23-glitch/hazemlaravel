<?php

namespace App\Filament\Resources\AppointmentTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class AppointmentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('menu_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Clinic')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('price')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                TextColumn::make('currency')
                    ->badge(),
                TextColumn::make('slots_count')
                    ->label('Slots')
                    ->counts('slots'),
                // Toggle the active clinic right from the list.
                ToggleColumn::make('is_active')
                    ->label('Active (open)'),
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
