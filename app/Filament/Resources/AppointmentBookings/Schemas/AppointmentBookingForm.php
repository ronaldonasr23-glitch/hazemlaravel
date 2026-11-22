<?php

namespace App\Filament\Resources\AppointmentBookings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AppointmentBookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('appointment_slot_id')
                    ->required()
                    ->numeric(),
                TextInput::make('appointment_type_id')
                    ->required()
                    ->numeric(),
                TextInput::make('patient_name')
                    ->required(),
                TextInput::make('patient_phone')
                    ->tel()
                    ->required(),
                Textarea::make('note')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('pending_payment'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('currency')
                    ->required()
                    ->default('EGP'),
                TextInput::make('payment_provider'),
                TextInput::make('payment_ref'),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
