<?php

namespace App\Filament\Pages;

use App\Models\Post;
use App\Support\ArticleImporter;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class ArticlePublisher extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    protected static ?string $navigationLabel = 'Schedule Articles';

    protected static ?string $title = 'Schedule Articles (CSV)';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.article-publisher';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('manage_posts');
    }

    public function mount(): void
    {
        $this->form->fill([
            'interval_days' => 1,
            'start_at'      => now()->addDay()->setTime(9, 0),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Upload articles')
                    ->description('Upload a CSV with the columns: Title, Content, Focus_Keyword, '
                        . 'Meta_Description, Keyphrase_Synonyms, AI_Summary, Schema_Type. Each row becomes '
                        . 'one scheduled article; they are published automatically one after another at the '
                        . 'cadence you choose.')
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV file')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv'])
                            ->storeFiles(false)
                            ->required(),
                        FileUpload::make('images_zip')
                            ->label('Thumbnails ZIP (optional)')
                            ->helperText('A .zip of images named 1, 2, 3 … matching the CSV rows (1.jpg → row 1, 2.jpg → row 2, …). Each becomes that article\'s thumbnail.')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/x-zip', 'multipart/x-zip'])
                            ->storeFiles(false),
                        Select::make('interval_days')
                            ->label('Publishing cadence')
                            ->options([
                                1 => 'Every day (يوميًا)',
                                2 => 'Every other day (يوم بعد يوم)',
                                3 => 'Every 3 days',
                                7 => 'Weekly (أسبوعيًا)',
                            ])
                            ->default(1)
                            ->required(),
                        DateTimePicker::make('start_at')
                            ->label('First article publishes at')
                            ->seconds(false)
                            ->helperText('The first article goes live at this date/time; each next one follows after the cadence above.')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function import(): void
    {
        $state = $this->form->getState();

        $file = $state['file'];
        $file = is_array($file) ? reset($file) : $file;
        $path = $file?->getRealPath();

        if (! $path) {
            Notification::make()->title('No file received')->danger()->send();

            return;
        }

        $zip = $state['images_zip'] ?? null;
        $zip = is_array($zip) ? reset($zip) : $zip;
        $zipPath = $zip?->getRealPath();

        $result = ArticleImporter::fromCsv(
            $path,
            (int) ($state['interval_days'] ?? 1),
            Carbon::parse($state['start_at']),
            $zipPath,
        );

        $range = $result['first'] && $result['last']
            ? $result['first']->format('M j, Y H:i') . ' → ' . $result['last']->format('M j, Y H:i')
            : '—';

        Notification::make()
            ->title('Articles scheduled')
            ->body("Created: {$result['created']} · Skipped: {$result['skipped']} · Publishing {$range}")
            ->success()
            ->persistent()
            ->send();

        $this->form->fill([
            'interval_days' => $result['interval'],
            'start_at'      => now()->addDay()->setTime(9, 0),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('import')->label('Schedule articles')->submit('import'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('attachThumbnails')
                ->label('Attach thumbnails to existing articles')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->modalHeading('Attach thumbnails to existing articles')
                ->modalDescription('Upload the SAME CSV you imported plus a ZIP of images named 1, 2, 3 … '
                    . 'Each image is matched to the article from the same CSV row (image 1 → row 1, …), so it works '
                    . 'even for articles you already imported and published without a thumbnail.')
                ->schema([
                    FileUpload::make('csv')
                        ->label('The articles CSV')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv'])
                        ->storeFiles(false)
                        ->required(),
                    FileUpload::make('zip')
                        ->label('Thumbnails ZIP')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/x-zip', 'multipart/x-zip'])
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $csv = $data['csv'];
                    $csv = is_array($csv) ? reset($csv) : $csv;
                    $csvPath = $csv?->getRealPath();

                    $zip = $data['zip'];
                    $zip = is_array($zip) ? reset($zip) : $zip;
                    $zipPath = $zip?->getRealPath();

                    if (! $csvPath || ! $zipPath) {
                        Notification::make()->title('Please provide both the CSV and the ZIP')->danger()->send();

                        return;
                    }

                    $r = ArticleImporter::attachThumbnails($csvPath, $zipPath);

                    if (($r['images'] ?? 0) === 0) {
                        $diag = ArticleImporter::lastZipDiag();
                        $hint = $diag['error']
                            ?? (! empty($diag['files'])
                                ? 'The ZIP opened but no images were named with a number. Found: '
                                    . implode(', ', array_slice($diag['files'], 0, 8))
                                    . (count($diag['files']) > 8 ? '…' : '')
                                : 'The ZIP appears to be empty.');

                        Notification::make()
                            ->title('No thumbnails were read from the ZIP')
                            ->body($hint)
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Thumbnails attached')
                        ->body("Updated {$r['updated']} article(s) from {$r['images']} image(s)."
                            . ($r['not_found'] ? " {$r['not_found']} row(s) had no matching article." : ''))
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }

    /** Upcoming scheduled articles, for the page view. */
    public function scheduledPosts(): Collection
    {
        return Post::query()
            ->where('status', 'scheduled')
            ->orderBy('published_at')
            ->get(['id', 'title', 'slug', 'published_at']);
    }
}
