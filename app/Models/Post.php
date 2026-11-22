<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasSeo;

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    /** The article's public URL = its exact original WordPress permalink. */
    public function url(): string
    {
        return '/' . ltrim($this->path ?: $this->slug, '/') . '/';
    }

    public function isRtl(): bool
    {
        return $this->html_dir === 'rtl' || $this->language === 'ar';
    }
}
