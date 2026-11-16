<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Blog articles (WordPress `post`). Backed by the shared `pages` table (so the
 * front-end keeps serving them 1:1 by path), but presented as their own admin
 * section via a global `wp_type = post` scope.
 */
class Article extends Page
{
    protected $table = 'pages';

    protected static function booted(): void
    {
        static::addGlobalScope('article', fn (Builder $q) => $q->where('wp_type', 'post'));
        static::creating(function (Article $m): void {
            $m->wp_type = 'post';
            $m->language = $m->language ?: 'en';
        });
    }
}
