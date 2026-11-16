<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Medical FAQ entries (WordPress `medical_faq`). Backed by the shared `pages`
 * table, presented as their own admin section via a `wp_type = medical_faq`
 * global scope.
 */
class Faq extends Page
{
    protected $table = 'pages';

    protected static function booted(): void
    {
        static::addGlobalScope('faq', fn (Builder $q) => $q->where('wp_type', 'medical_faq'));
        static::creating(function (Faq $m): void {
            $m->wp_type = 'medical_faq';
            $m->language = $m->language ?: 'en';
        });
    }
}
