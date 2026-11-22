<?php

namespace App\Filament\Resources\AppointmentBookings\Pages;

use App\Filament\Resources\AppointmentBookings\AppointmentBookingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppointmentBooking extends EditRecord
{
    protected static string $resource = AppointmentBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
