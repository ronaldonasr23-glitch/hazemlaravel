<?php

namespace App\Support;

use App\Models\AppointmentBooking;
use App\Models\AppointmentSlot;
use Illuminate\Support\Facades\DB;

/**
 * Core appointment actions, shared by the public booking page and the Telegram
 * bot (walk-in slot control). Slot state transitions are done under a row lock
 * so two people can't book the same slot.
 */
class Appointments
{
    /**
     * Book a slot for a patient. Returns the booking, or null if the slot is not
     * bookable (taken/closed, or its clinic is not currently active).
     *
     * Payment is OPTIONAL: an unpaid booking is recorded as `unconfirmed` and the
     * slot stays OPEN (others can still book it) — the doctor is notified that an
     * unconfirmed booking needs follow-up. Only payment (Stripe, wired later via
     * confirmPaid()) marks the booking `paid` and closes the slot.
     */
    public static function book(int $slotId, string $name, string $phone, ?string $note = null): ?AppointmentBooking
    {
        return DB::transaction(function () use ($slotId, $name, $phone, $note) {
            /** @var AppointmentSlot|null $slot */
            $slot = AppointmentSlot::with('type')->lockForUpdate()->find($slotId);

            if (! $slot || ! $slot->isBookable()) {
                return null;
            }

            $booking = AppointmentBooking::create([
                'appointment_slot_id' => $slot->id,
                'appointment_type_id' => $slot->appointment_type_id,
                'patient_name'        => $name,
                'patient_phone'       => $phone,
                'note'                => $note,
                'status'              => 'unconfirmed',
                'amount'              => $slot->type->price,
                'currency'            => $slot->type->currency,
            ]);

            self::notifyBooked($booking->fresh(['slot', 'type']));

            return $booking;
        });
    }

    /**
     * Confirm a booking as paid (called by the Stripe flow once payment succeeds):
     * mark it paid and close its slot so no one else can take it.
     */
    public static function confirmPaid(AppointmentBooking $booking, ?string $paymentRef = null): void
    {
        DB::transaction(function () use ($booking, $paymentRef) {
            $booking->update([
                'status'           => 'paid',
                'payment_provider' => 'stripe',
                'payment_ref'      => $paymentRef,
                'paid_at'          => now(),
            ]);
            $booking->slot?->update(['status' => 'booked']);

            Telegram::notify('✅ <b>Payment confirmed</b> — ' . $booking->patient_name
                . ' · ' . optional($booking->slot?->starts_at)->format('D j M H:i'));
        });
    }

    /** Close a slot (walk-in / unavailable). Idempotent. */
    public static function closeSlot(AppointmentSlot $slot): void
    {
        if ($slot->status !== 'booked') {
            $slot->update(['status' => 'closed', 'held_until' => null]);
        }
    }

    /** Re-open a previously closed slot. */
    public static function openSlot(AppointmentSlot $slot): void
    {
        if ($slot->status === 'closed') {
            $slot->update(['status' => 'available', 'held_until' => null]);
        }
    }

    private static function notifyBooked(AppointmentBooking $b): void
    {
        $when = $b->slot?->starts_at?->format('D, j M Y — H:i');
        $text = "⚠️ <b>Unconfirmed appointment</b> (not paid — please follow up)\n"
            . "👤 {$b->patient_name}\n"
            . "📞 {$b->patient_phone}\n"
            . "🏥 " . ($b->type?->name ?? '-') . "\n"
            . "🕒 {$when}\n"
            . "💳 {$b->amount} {$b->currency}";

        Telegram::notify($text);
    }
}
