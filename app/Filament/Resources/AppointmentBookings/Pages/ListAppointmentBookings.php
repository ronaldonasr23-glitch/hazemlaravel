<?php

namespace App\Filament\Resources\AppointmentBookings\Pages;

use App\Filament\Resources\AppointmentBookings\AppointmentBookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppointmentBookings extends ListRecords
{
    protected static string $resource = AppointmentBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
