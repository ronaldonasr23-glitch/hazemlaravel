<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    use \App\Filament\Concerns\InteractsWithSeo;

    protected static string $resource = ArticleResource::class;
}
