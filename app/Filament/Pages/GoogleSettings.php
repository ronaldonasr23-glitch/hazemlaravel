<?php

namespace App\Filament\Pages;

use App\Filament\Pages\IndexOnGoogle;
use App\Models\Setting;
use App\Support\GoogleIndexing;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Crypt;

class GoogleSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Google & Marketing';

    protected static ?string $title = 'Google & Marketing Integrations';

    protected string $view = 'filament.pages.google-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('manage_google');
    }

    public function mount(): void
    {
        $this->form->fill([
            'google_site_verification' => Setting::get('google_site_verification'),
            'ga4_measurement_id'       => Setting::get('ga4_measurement_id'),
            'gtm_id'                   => Setting::get('gtm_id'),
            'google_ads_tag'           => Setting::get('google_ads_tag'),
            'google_ads_conversion'    => Setting::get('google_ads_conversion'),
            'search_console_property'  => Setting::get('search_console_property'),
            'google_indexing_enabled'  => (bool) Setting::get('google_indexing_enabled'),
            // google_service_account_json is intentionally never pre-filled.
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Google Search Console')
                    ->description('Verify ownership and connect your property.')
                    ->schema([
                        TextInput::make('google_site_verification')
                            ->label('Site verification code')
                            ->helperText('The content value from the google-site-verification meta tag.'),
                        TextInput::make('search_console_property')
                            ->label('Property URL')
                            ->placeholder('https://chemgrowgas.com/'),
                    ]),
                Section::make('Google Analytics 4 / Tag Manager')
                    ->schema([
                        TextInput::make('ga4_measurement_id')
                            ->label('GA4 Measurement ID')
                            ->placeholder('G-XXXXXXXXXX'),
                        TextInput::make('gtm_id')
                            ->label('Google Tag Manager ID')
                            ->placeholder('GTM-XXXXXXX'),
                    ]),
                Section::make('Google Ads')
                    ->schema([
                        TextInput::make('google_ads_tag')
                            ->label('Google Ads Tag ID')
                            ->placeholder('AW-XXXXXXXXX'),
                        TextInput::make('google_ads_conversion')
                            ->label('Conversion label')
                            ->placeholder('AW-XXXXXXXXX/XXXXXXXXXXXXXXX'),
                    ]),
                Section::make('Google Indexing API (auto-submit articles)')
                    ->description('When enabled, every published article is sent to Google for indexing '
                        . 'automatically — including future scheduled articles, the moment they go live. '
                        . 'Scheduled (not-yet-published) articles are never sent.')
                    ->icon(Heroicon::OutlinedRocketLaunch)
                    ->schema([
                        Toggle::make('google_indexing_enabled')
                            ->label('Auto-submit published articles to Google')
                            ->helperText(fn () => GoogleIndexing::configured()
                                ? ('Service account connected: ' . GoogleIndexing::clientEmail())
                                : 'Paste the service account JSON below, then turn this on.'),
                        Textarea::make('google_service_account_json')
                            ->label('Service account JSON key')
                            ->rows(6)
                            ->extraInputAttributes(['style' => 'font-family:monospace;font-size:11px;'])
                            ->placeholder(GoogleIndexing::configured()
                                ? '•••••••• A key is already saved. Paste a new one only to replace it.'
                                : '{ "type": "service_account", "project_id": "...", "private_key": "...", "client_email": "...@...gserviceaccount.com", ... }')
                            ->helperText('Stored encrypted. Create it in Google Cloud → IAM → Service Accounts, '
                                . 'enable the Indexing API, and add this email as an Owner in Search Console. '
                                . 'Leave blank to keep the saved key.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Service account JSON: encrypt when provided, keep existing when blank.
        if (array_key_exists('google_service_account_json', $state)) {
            if (filled($state['google_service_account_json'])) {
                Setting::put('google_service_account_json', Crypt::encryptString($state['google_service_account_json']), 'google');
            }
            unset($state['google_service_account_json']);
        }

        foreach ($state as $key => $value) {
            Setting::put($key, $value, 'google');
        }

        Notification::make()->title('Google settings saved')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Save settings')->submit('save'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testIndexing')
                ->label('Test connection')
                ->icon(Heroicon::OutlinedSignal)
                ->color('gray')
                ->action(function (): void {
                    if (! GoogleIndexing::configured()) {
                        Notification::make()->title('No service account saved yet')->warning()->send();

                        return;
                    }

                    if (GoogleIndexing::accessToken()) {
                        Notification::make()
                            ->title('Connected to Google ✓')
                            ->body('Service account: ' . GoogleIndexing::clientEmail())
                            ->success()->send();
                    } else {
                        Notification::make()
                            ->title('Could not authenticate')
                            ->body('Check the JSON key, that the Indexing API is enabled, and the clock/credentials are valid.')
                            ->danger()->persistent()->send();
                    }
                }),
            Action::make('openIndexing')
                ->label('Check & submit articles →')
                ->icon(Heroicon::OutlinedRocketLaunch)
                // All indexing operations live on the step-by-step "Index on Google" page.
                ->url(fn () => IndexOnGoogle::getUrl()),
        ];
    }
}
