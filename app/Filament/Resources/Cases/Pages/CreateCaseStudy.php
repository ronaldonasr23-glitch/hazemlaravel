<?php

namespace App\Filament\Resources\Cases\Pages;

use App\Filament\Resources\Cases\CaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCaseStudy extends CreateRecord
{
    use \App\Filament\Concerns\InteractsWithSeo;

    protected static string $resource = CaseResource::class;
}
