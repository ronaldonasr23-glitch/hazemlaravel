<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online appointments: the doctor manages visit types and their time slots from
 * the dashboard; visitors book an available slot (name + phone), which holds the
 * slot pending payment (Stripe, wired later) and notifies the doctor on Telegram.
 * The doctor can also close/open slots from the Telegram bot (walk-ins).
 */
return new class extends Migration
{
    public function up(): void
    {
        // A kind of visit the doctor offers (examination / consultation / follow-up…),
        // each with its own price and slots.
        Schema::create('appointment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // e.g. "Examination", "كشف"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->decimal('price', 10, 2)->default(0);   // for Stripe later
            $table->string('currency', 3)->default('EGP');
            $table->string('color', 20)->nullable();        // UI accent
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('menu_order')->default(0);
            $table->timestamps();
        });

        // A bookable time for a given visit type.
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_type_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            // available = bookable; held = awaiting payment; booked = taken;
            // closed = manually closed (e.g. walk-in via Telegram).
            $table->string('status', 16)->default('available');
            $table->timestamp('held_until')->nullable();    // reservation expiry during checkout
            $table->timestamps();

            $table->index(['appointment_type_id', 'starts_at']);
            $table->index('status');
        });

        // A visitor's booking of a slot.
        Schema::create('appointment_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->constrained()->cascadeOnDelete();
            $table->string('patient_name');
            $table->string('patient_phone', 40);
            $table->text('note')->nullable();
            // pending_payment -> paid (confirmed) | cancelled | expired
            $table->string('status', 20)->default('pending_payment');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->string('payment_provider', 20)->nullable();   // 'stripe'
            $table->string('payment_ref')->nullable();            // Stripe session/intent id
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_bookings');
        Schema::dropIfExists('appointment_slots');
        Schema::dropIfExists('appointment_types');
    }
};
