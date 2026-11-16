<?php

namespace App\Filament\Resources\Portfolios\Pages;

use App\Filament\Resources\Portfolios\PortfolioResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePortfolio extends CreateRecord
{
    use \App\Filament\Concerns\InteractsWithSeo;

    protected static string $resource = PortfolioResource::class;
}
