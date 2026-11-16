<?php

namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    use HasSeo;

    protected $guarded = [];

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }
}
