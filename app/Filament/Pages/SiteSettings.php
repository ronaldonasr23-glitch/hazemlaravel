<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\MailSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?string $title = 'Site Settings';

    protected string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('manage_settings');
    }

    /** The SMTP / email section is restricted to admins. */
    protected static function isAdmin(): bool
    {
        return (bool) auth()->user()?->hasRole('admin');
    }

    public function mount(): void
    {
        $mail = MailSettings::stored();

        $this->form->fill([
            'site_name'         => Setting::get('site_name'),
            'header_html'       => Setting::get('header_html'),
            'footer_html'       => Setting::get('footer_html'),
            'contact_email'       => Setting::get('contact_email'),
            'contact_email_extra' => Setting::get('contact_email_extra'),
            'mail_mailer'       => $mail['mail_mailer'] ?: 'smtp',
            'mail_host'         => $mail['mail_host'],
            'mail_port'         => $mail['mail_port'] ?: 587,
            'mail_encryption'   => $mail['mail_encryption'] ?: 'tls',
            'mail_username'     => $mail['mail_username'],
            'mail_from_address' => $mail['mail_from_address'],
            'mail_from_name'    => $mail['mail_from_name'],
            // mail_password is intentionally never pre-filled.
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->schema([
                TextInput::make('site_name')->label('Site name')->columnSpanFull(),
            ]),
            Section::make('Header / Footer (HTML)')
                ->description('Shared markup rendered on every page. Edit with care.')
                ->collapsed()
                ->schema([
                    Textarea::make('header_html')->rows(10)
                        ->extraInputAttributes(['style' => 'font-family:monospace;font-size:12px;'])->columnSpanFull(),
                    Textarea::make('footer_html')->rows(10)
                        ->extraInputAttributes(['style' => 'font-family:monospace;font-size:12px;'])->columnSpanFull(),
                ]),
            Section::make('Email / SMTP')
                ->description('SMTP server used to deliver the website contact form. Admins only.')
                ->icon(Heroicon::OutlinedEnvelope)
                ->visible(fn () => static::isAdmin())
                ->columns(2)
                ->schema([
                    Select::make('mail_mailer')
                        ->label('Mail driver')
                        ->options([
                            'smtp' => 'SMTP (send real emails)',
                            'log'  => 'Log only (do not send)',
                        ])
                        ->default('smtp')
                        ->native(false)
                        ->live()
                        ->required(),
                    TextInput::make('contact_email')
                        ->label('Contact form recipient (main)')
                        ->email()
                        ->placeholder('info@chemgrowgas.com')
                        ->helperText('The primary inbox that receives every contact message.'),
                    Textarea::make('contact_email_extra')
                        ->label('Additional recipients')
                        ->rows(2)
                        ->placeholder("ronaldonasr23@gmail.com\nsales@chemgrowgas.com")
                        ->helperText('Optional. One email per line (or comma-separated). These also receive every message.')
                        ->columnSpanFull(),
                    TextInput::make('mail_host')
                        ->label('SMTP host')
                        ->placeholder('smtp.hostinger.com')
                        ->visible(fn ($get) => $get('mail_mailer') === 'smtp')
                        ->requiredIf('mail_mailer', 'smtp'),
                    TextInput::make('mail_port')
                        ->label('Port')
                        ->numeric()
                        ->placeholder('465')
                        ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),
                    Select::make('mail_encryption')
                        ->label('Encryption')
                        ->options([
                            'ssl'  => 'SSL (port 465)',
                            'tls'  => 'TLS / STARTTLS (port 587)',
                            'none' => 'None',
                        ])
                        ->default('ssl')
                        ->native(false)
                        ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),
                    TextInput::make('mail_username')
                        ->label('SMTP username')
                        ->placeholder('info@chemgrowgas.com')
                        ->autocomplete('off')
                        ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),
                    TextInput::make('mail_password')
                        ->label('SMTP password')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->placeholder('•••••••• (leave blank to keep current)')
                        ->helperText('Stored encrypted. Leave empty to keep the saved password.')
                        ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),
                    TextInput::make('mail_from_address')
                        ->label('From address')
                        ->email()
                        ->placeholder('info@chemgrowgas.com')
                        ->helperText('Address visitors see as the sender.'),
                    TextInput::make('mail_from_name')
                        ->label('From name')
                        ->placeholder('Grow Gas'),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Password: encrypt when provided, keep existing when left blank.
        if (array_key_exists('mail_password', $state)) {
            if (filled($state['mail_password'])) {
                Setting::put('mail_password', Crypt::encryptString($state['mail_password']), 'mail');
            }
            unset($state['mail_password']);
        }

        foreach ($state as $key => $value) {
            $group = (str_starts_with($key, 'mail_') || str_starts_with($key, 'contact_')) ? 'mail' : 'general';
            Setting::put($key, $value, $group);
        }

        Notification::make()->title('Settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        if (! static::isAdmin()) {
            return [];
        }

        return [
            Action::make('sendTestEmail')
                ->label('Send test email')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->schema([
                    TextInput::make('to')
                        ->label('Send to')
                        ->email()
                        ->required()
                        ->default(fn () => Setting::get('contact_email') ?: auth()->user()?->email),
                ])
                ->action(function (array $data): void {
                    // Apply the settings currently on the form (even unsaved),
                    // falling back to the stored password when left blank.
                    $values = $this->form->getState();

                    if (empty($values['mail_password'])) {
                        $values['mail_password'] = MailSettings::stored()['mail_password'] ?? null;
                    }

                    MailSettings::apply($values);

                    try {
                        Mail::raw(
                            'This is a test email sent from the ' . config('app.name') . ' admin Site Settings page. '
                                . 'If you received it, your SMTP configuration is working.',
                            fn ($mail) => $mail->to($data['to'])->subject('SMTP test • ' . config('app.name'))
                        );

                        Notification::make()
                            ->title('Test email sent')
                            ->body('Sent to ' . $data['to'] . '. Check the inbox (and the spam folder).')
                            ->success()
                            ->persistent()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Could not send the email')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
