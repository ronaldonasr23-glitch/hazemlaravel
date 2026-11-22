<?php

namespace App\Filament\Resources\AppointmentBookings;

use App\Filament\Resources\AppointmentBookings\Pages\CreateAppointmentBooking;
use App\Filament\Resources\AppointmentBookings\Pages\EditAppointmentBooking;
use App\Filament\Resources\AppointmentBookings\Pages\ListAppointmentBookings;
use App\Filament\Resources\AppointmentBookings\Schemas\AppointmentBookingForm;
use App\Filament\Resources\AppointmentBookings\Tables\AppointmentBookingsTable;
use App\Models\AppointmentBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AppointmentBookingResource extends Resource
{
    use \App\Filament\Concerns\AuthorizesWithPermission;

    protected static ?string $model = AppointmentBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Appointments';

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return AppointmentBookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentBookingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointmentBookings::route('/'),
            'create' => CreateAppointmentBooking::route('/create'),
            'edit' => EditAppointmentBooking::route('/{record}/edit'),
        ];
    }
}
