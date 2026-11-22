<?php

namespace App\Filament\Resources\AppointmentSlots\Schemas;

use App\Models\AppointmentType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class AppointmentSlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('appointment_type_id')
                    ->label('Clinic')
                    ->options(fn () => AppointmentType::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label('Date & time')
                    ->seconds(false)
                    ->required(),
                Select::make('status')
                    ->options([
                        'available' => 'Available',
                        'held'      => 'Held',
                        'booked'    => 'Booked',
                        'closed'    => 'Closed',
                    ])
                    ->default('available')
                    ->required(),
            ]);
    }
}
