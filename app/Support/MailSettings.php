<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Reads SMTP/mail settings stored in the `settings` table (managed from the
 * admin Site Settings page) and applies them to Laravel's mail config at
 * runtime, so email can be configured from the dashboard without touching .env.
 */
class MailSettings
{
    /** Non-secret keys persisted as plain settings. */
    public const KEYS = [
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_encryption',
        'mail_username',
        'mail_from_address',
        'mail_from_name',
    ];

    /** Saved mail settings as an array, password decrypted. */
    public static function stored(): array
    {
        $v = [];

        foreach (self::KEYS as $key) {
            $v[$key] = Setting::get($key);
        }

        $v['mail_password'] = self::decrypt(Setting::get('mail_password'));

        return $v;
    }

    /** Decrypt a stored password; tolerate a value that was never encrypted. */
    public static function decrypt(?string $password): ?string
    {
        if (! $password) {
            return null;
        }

        try {
            return Crypt::decryptString($password);
        } catch (\Throwable $e) {
            return $password;
        }
    }

    /** Override the live mail config from a values array. */
    public static function apply(array $v): void
    {
        $mailer = $v['mail_mailer'] ?: 'smtp';

        // Nothing configured yet: leave the .env defaults in place.
        if ($mailer === 'smtp' && empty($v['mail_host'])) {
            return;
        }

        $encryption = $v['mail_encryption'] ?? null;

        config([
            'mail.default'                 => $mailer,
            'mail.mailers.smtp.host'       => $v['mail_host'] ?? null,
            'mail.mailers.smtp.port'       => (int) ($v['mail_port'] ?: 587),
            'mail.mailers.smtp.username'   => $v['mail_username'] ?: null,
            'mail.mailers.smtp.password'   => $v['mail_password'] ?: null,
            // Symfony mailer: 'smtps' means implicit TLS (port 465); otherwise
            // STARTTLS is negotiated automatically (port 587).
            'mail.mailers.smtp.scheme'     => $encryption === 'ssl' ? 'smtps' : null,
            'mail.mailers.smtp.encryption' => in_array($encryption, ['tls', 'ssl'], true) ? $encryption : null,
        ]);

        if (! empty($v['mail_from_address'])) {
            config(['mail.from.address' => $v['mail_from_address']]);
        }

        if (! empty($v['mail_from_name'])) {
            config(['mail.from.name' => $v['mail_from_name']]);
        }
    }

    /** Called from AppServiceProvider::boot() on every request. */
    public static function boot(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $values = self::stored();
        } catch (\Throwable $e) {
            // DB not ready (e.g. during migrate/install): skip silently.
            return;
        }

        self::apply($values);
    }
}
