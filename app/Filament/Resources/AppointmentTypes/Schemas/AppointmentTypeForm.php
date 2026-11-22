<?php

namespace App\Filament\Resources\AppointmentTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AppointmentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Clinic name')
                    ->placeholder('e.g. Egypt Clinic')
                    ->required(),
                Select::make('currency')
                    ->options(['EGP' => 'Egyptian Pound (EGP)', 'AED' => 'UAE Dirham (AED)'])
                    ->default('EGP')
                    ->required(),
                TextInput::make('price')
                    ->label('Price')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('duration_minutes')
                    ->label('Slot length (minutes)')
                    ->numeric()
                    ->default(30),
                Toggle::make('is_active')
                    ->label('Active (doctor is currently here — open for booking)')
                    ->helperText('Turn ON the clinic you are currently at. Only active clinics accept bookings; turn the other OFF to close it.')
                    ->default(false),
                Textarea::make('description')
                    ->label('Notes (optional)')
                    ->rows(2),
            ]);
    }
}
