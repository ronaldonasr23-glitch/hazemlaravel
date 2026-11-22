<?php

namespace App\Filament\Resources\AppointmentSlots\Tables;

use App\Models\AppointmentSlot;
use App\Models\AppointmentType;
use App\Support\Appointments;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppointmentSlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at')
            ->columns([
                TextColumn::make('type.name')
                    ->label('Clinic')
                    ->badge()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Date & time')
                    ->dateTime('D, j M Y — H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'available' => 'success',
                        'booked'    => 'warning',
                        'held'      => 'info',
                        'closed'    => 'danger',
                        default     => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('appointment_type_id')
                    ->label('Clinic')
                    ->options(fn () => AppointmentType::orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'held'      => 'Held',
                        'booked'    => 'Booked',
                        'closed'    => 'Closed',
                    ]),
            ])
            ->recordActions([
                Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (AppointmentSlot $r) => $r->status === 'available')
                    ->requiresConfirmation()
                    ->action(fn (AppointmentSlot $r) => Appointments::closeSlot($r)),
                Action::make('open')
                    ->label('Re-open')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (AppointmentSlot $r) => $r->status === 'closed')
                    ->action(fn (AppointmentSlot $r) => Appointments::openSlot($r)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
