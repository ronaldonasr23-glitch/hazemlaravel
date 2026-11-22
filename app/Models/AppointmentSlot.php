<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentSlot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at'  => 'datetime',
        'held_until' => 'datetime',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(AppointmentBooking::class);
    }

    /** Bookable: available, in the future, and in a currently-active clinic. */
    public function scopeBookable($q)
    {
        return $q->where('status', 'available')
            ->where('starts_at', '>', now())
            ->whereHas('type', fn ($t) => $t->where('is_active', true));
    }

    public function isBookable(): bool
    {
        return $this->status === 'available'
            && $this->starts_at?->isFuture()
            && (bool) $this->type?->is_active;
    }
}
