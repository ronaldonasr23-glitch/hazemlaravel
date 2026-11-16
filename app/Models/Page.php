<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasSeo;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Safety net: never emit a leftover "noindex" robots meta (from the old
     * WordPress staging capture), whatever is stored in the database. Keeps the
     * healthy "index, follow" robots meta intact.
     */
    protected function headHtml(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : preg_replace(
                '/<meta[^>]*name=["\']robots["\'][^>]*noindex[^>]*>\s*/i',
                '',
                $value
            ),
        );
    }
}
