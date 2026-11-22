<?php

namespace Database\Seeders;

use App\Models\AppointmentSlot;
use App\Models\AppointmentType;
use Illuminate\Database\Seeder;

/**
 * Two clinics the doctor works from, each with its own currency and price. Only
 * the ACTIVE clinic's slots are bookable — the doctor toggles which one he is
 * currently at from the dashboard (e.g. in Egypt → Egypt open, UAE closed).
 * Prices/currencies are edited from the dashboard afterwards.
 */
class AppointmentsSeeder extends Seeder
{
    public function run(): void
    {
        $clinics = [
            ['name' => 'Egypt Clinic', 'currency' => 'EGP', 'price' => 500, 'is_active' => true,  'menu_order' => 1, 'duration_minutes' => 30],
            ['name' => 'UAE Clinic',   'currency' => 'AED', 'price' => 300, 'is_active' => false, 'menu_order' => 2, 'duration_minutes' => 30],
        ];

        foreach ($clinics as $c) {
            $clinic = AppointmentType::firstOrCreate(['name' => $c['name']], $c);

            // Seed ~2 weeks of slots (skip Fridays) at common clinic hours, if none yet.
            if ($clinic->slots()->count() > 0) {
                continue;
            }

            for ($d = 1; $d <= 14; $d++) {
                $day = now()->startOfDay()->addDays($d);
                if ($day->isFriday()) {
                    continue;
                }
                foreach (['16:00', '17:00', '18:00', '19:00', '20:00'] as $time) {
                    [$h, $m] = explode(':', $time);
                    AppointmentSlot::create([
                        'appointment_type_id' => $clinic->id,
                        'starts_at'           => $day->copy()->setTime((int) $h, (int) $m),
                        'status'              => 'available',
                    ]);
                }
            }
        }
    }
}
