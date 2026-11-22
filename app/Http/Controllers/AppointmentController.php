<?php

namespace App\Http\Controllers;

use App\Models\AppointmentType;
use App\Support\Appointments;
use App\Support\SiteShell;
use Illuminate\Http\Request;

/**
 * Public online-appointment page: shows the doctor's visit types and their
 * available slots (managed from the dashboard), and books a slot for a visitor.
 * Rendered inside the captured site chrome via SiteShell, like the blog.
 */
class AppointmentController extends Controller
{
    public function index()
    {
        // Only slots from currently-active clinics are offered, each carrying its
        // clinic's price + currency (Egypt EGP / UAE AED). The page shows a
        // calendar; the data drives which days/times are available.
        $slots = \App\Models\AppointmentSlot::with('type')
            ->bookable()->orderBy('starts_at')->get()
            ->map(fn ($s) => [
                'id'       => $s->id,
                'date'     => $s->starts_at->format('Y-m-d'),
                'time'     => $s->starts_at->format('H:i'),
                'label'    => $s->starts_at->format('l j M Y — H:i'),
                'clinic'   => $s->type->name,
                'price'    => rtrim(rtrim(number_format((float) $s->type->price, 2), '0'), '.'),
                'currency' => $s->type->currency,
            ])->values();

        $activeClinics = AppointmentType::active()->orderBy('menu_order')->get();

        $inner = view('dynamic.appointment', [
            'slots'         => $slots,
            'activeClinics' => $activeClinics,
        ])->render();

        return SiteShell::render('en', $inner, [
            'title'       => 'Book an Appointment - Prof. Dr. Hazem El Fezary',
            'description' => 'Book an online appointment with Prof. Dr. Hazem El Fezary — pick a day and time from the calendar.',
            'canonical'   => url('/online-appointment/'),
            'og_type'     => 'website',
        ]);
    }

    public function book(Request $request)
    {
        $data = $request->validate([
            'slot_id' => ['required', 'integer', 'exists:appointment_slots,id'],
            'name'    => ['required', 'string', 'max:120'],
            'phone'   => ['required', 'string', 'max:40'],
            'note'    => ['nullable', 'string', 'max:500'],
        ]);

        $booking = Appointments::book($data['slot_id'], $data['name'], $data['phone'], $data['note'] ?? null);

        if (! $booking) {
            return back()
                ->withInput()
                ->with('booking_error', 'Sorry, that time was just taken. Please choose another slot.');
        }

        // Payment (Stripe) plugs in here later: redirect to checkout, then confirm
        // on return. For now the booking is recorded and the doctor is notified.
        return redirect('/online-appointment/?booked=' . $booking->id);
    }
}
