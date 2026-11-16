<?php

namespace App\Filament\Resources\Redirects\Pages;

use App\Filament\Resources\Redirects\RedirectResource;
use App\Support\RedirectImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import from CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Import redirects from a CSV')
                ->modalDescription('Upload a crawl/coverage export (e.g. from Google Search Console). '
                    . 'The tool reads every URL, auto-detects the matching current page, and creates a 301 redirect. '
                    . 'URLs that are already correct are skipped.')
                ->schema([
                    FileUpload::make('file')
                        ->label('CSV file')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv'])
                        ->storeFiles(false)
                        ->required(),
                    Toggle::make('overwrite')
                        ->label('Update existing redirects')
                        ->helperText('If a redirect for the same path already exists, replace its target.')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $file = $data['file'];
                    $path = is_array($file) ? reset($file)->getRealPath() : $file->getRealPath();

                    $r = RedirectImporter::fromCsv($path, (bool) ($data['overwrite'] ?? true));

                    Notification::make()
                        ->title('Import finished')
                        ->body("Created: {$r['created']} · Updated: {$r['updated']} · Skipped (already correct): {$r['skipped']}")
                        ->success()
                        ->persistent()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
