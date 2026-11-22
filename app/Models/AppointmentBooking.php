<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentBooking extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class, 'appointment_slot_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }

    public function scopeConfirmed($q)
    {
        return $q->where('status', 'paid');
    }
}
