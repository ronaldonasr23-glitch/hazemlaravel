<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use \App\Filament\Concerns\InteractsWithSeo;

    protected static string $resource = PageResource::class;
}
