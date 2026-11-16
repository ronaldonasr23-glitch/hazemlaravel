<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Patient cases (WordPress `cases`). Backed by the shared `pages` table,
 * presented as their own admin section via a `wp_type = cases` global scope.
 */
class CaseStudy extends Page
{
    protected $table = 'pages';

    protected static function booted(): void
    {
        static::addGlobalScope('case', fn (Builder $q) => $q->where('wp_type', 'cases'));
        static::creating(function (CaseStudy $m): void {
            $m->wp_type = 'cases';
            $m->language = $m->language ?: 'en';
        });
    }
}
