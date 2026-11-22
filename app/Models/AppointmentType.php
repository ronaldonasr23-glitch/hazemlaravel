<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AppointmentType extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $type) {
            if (empty($type->slug)) {
                $base = Str::slug($type->name) ?: 'type';
                $slug = $base;
                $n = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $n++;
                }
                $type->slug = $slug;
            }
        });
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
