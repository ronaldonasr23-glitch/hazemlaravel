<?php

namespace App\Support;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Google Indexing API integration. Signs a service-account JWT with openssl
 * (no external package), exchanges it for an access token, and notifies Google
 * that article URLs were updated — so published articles are submitted for
 * crawling automatically. Scheduled (not-yet-published) posts are never sent.
 */
class GoogleIndexing
{
    public const SCOPE_INDEXING = 'https://www.googleapis.com/auth/indexing';

    public const SCOPE_INSPECT = 'https://www.googleapis.com/auth/webmasters.readonly';

    public static function enabled(): bool
    {
        return (bool) Setting::get('google_indexing_enabled') && self::credentials() !== null;
    }

    /** True when a (valid-looking) service account is stored, regardless of the toggle. */
    public static function configured(): bool
    {
        return self::credentials() !== null;
    }

    /** Decoded service-account credentials, or null when missing/invalid. */
    public static function credentials(): ?array
    {
        $raw = (string) Setting::get('google_service_account_json');
        if (trim($raw) === '') {
            return null;
        }

        try {
            $json = Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            $json = $raw; // tolerate a value that was stored un-encrypted
        }

        $data = json_decode($json, true);

        if (! is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
            return null;
        }

        return $data;
    }

    public static function clientEmail(): ?string
    {
        return self::credentials()['client_email'] ?? null;
    }

    /** OAuth2 access token for the given scope (cached ~55 min, per scope). */
    public static function accessToken(string $scope = self::SCOPE_INDEXING): ?string
    {
        $cred = self::credentials();
        if (! $cred) {
            return null;
        }

        return Cache::remember('google_token_' . md5($cred['client_email'] . '|' . $scope), 3300, function () use ($cred, $scope) {
            $tokenUri = $cred['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $now = time();

            $jwt = self::signJwt([
                'iss'   => $cred['client_email'],
                'scope' => $scope,
                'aud'   => $tokenUri,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ], $cred['private_key']);

            if (! $jwt) {
                return null;
            }

            try {
                $resp = Http::asForm()->timeout(15)->post($tokenUri, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]);
            } catch (\Throwable $e) {
                return null;
            }

            return $resp->ok() ? ($resp->json('access_token') ?: null) : null;
        });
    }

    protected static function signJwt(array $claim, string $privateKey): ?string
    {
        $segments = [
            self::b64((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            self::b64((string) json_encode($claim)),
        ];

        $signature = '';
        if (! @openssl_sign(implode('.', $segments), $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        $segments[] = self::b64($signature);

        return implode('.', $segments);
    }

    protected static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** Public site base URL (never the local dev host). */
    public static function base(): string
    {
        $site = rtrim((string) config('app.url'), '/');
        $host = parse_url($site, PHP_URL_HOST) ?: $site;

        if ($site === '' || preg_match('#(localhost|127\.0\.0\.1|\.test)$#i', $host)) {
            $site = 'https://chemgrowgas.com';
        }

        return $site;
    }

    public static function urlFor(Post $post): string
    {
        // The canonical URL = the article's FULL path (keeps the /ar/ prefix for
        // Arabic articles) with a trailing slash, percent-encoded exactly like the
        // sitemap. Using just the slug dropped the /ar/ prefix and the trailing
        // slash, so Google was asked about URLs that 404 / don't match the index.
        $path = trim((string) ($post->path ?: $post->slug), '/');
        $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));

        return self::base() . '/' . $encoded . '/';
    }

    /** Notify Google that a URL was updated. Returns [bool success, string message]. */
    public static function publish(string $url, string $type = 'URL_UPDATED'): array
    {
        $token = self::accessToken();
        if (! $token) {
            return [false, 'Authentication failed — check the service account JSON.'];
        }

        try {
            $resp = Http::withToken($token)->timeout(15)
                ->post('https://indexing.googleapis.com/v3/urlNotifications:publish', [
                    'url'  => $url,
                    'type' => $type,
                ]);
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }

        if ($resp->ok()) {
            return [true, 'submitted'];
        }

        return [false, $resp->json('error.message') ?: ('HTTP ' . $resp->status())];
    }

    /** The Search Console property id used by the URL Inspection API. */
    public static function siteUrl(): string
    {
        $prop = Setting::get('gsc_property') ?: Setting::get('search_console_property');

        return $prop ?: ('sc-domain:' . preg_replace('#^https?://#', '', self::base()));
    }

    /**
     * Query the URL Inspection API for a page's real Google index status.
     *
     * @return array{0:bool,1:?string,2:string} [ok, coverageState, message]
     */
    public static function inspect(string $url): array
    {
        $token = self::accessToken(self::SCOPE_INSPECT);
        if (! $token) {
            return [false, null, 'Authentication failed — check the service account JSON.'];
        }

        try {
            $resp = Http::withToken($token)->timeout(20)
                ->post('https://searchconsole.googleapis.com/v1/urlInspection/index:inspect', [
                    'inspectionUrl' => $url,
                    'siteUrl'       => self::siteUrl(),
                ]);
        } catch (\Throwable $e) {
            return [false, null, $e->getMessage()];
        }

        if (! $resp->ok()) {
            return [false, null, $resp->json('error.message') ?: ('HTTP ' . $resp->status())];
        }

        $coverage = $resp->json('inspectionResult.indexStatusResult.coverageState')
            ?: ($resp->json('inspectionResult.indexStatusResult.verdict') ?: 'Unknown');

        return [true, $coverage, 'ok'];
    }

    /** Reduce a raw coverageState to 'indexed' | 'not_indexed' | 'unknown'. */
    public static function classify(?string $coverage): string
    {
        if (! $coverage) {
            return 'unknown';
        }

        $c = strtolower($coverage);

        if (str_contains($c, 'indexed') && ! str_contains($c, 'not indexed')) {
            return 'indexed';
        }

        if (str_contains($c, 'unknown')) {
            return 'unknown';
        }

        return 'not_indexed';
    }

    /**
     * Inspect every published article and store its coverage.
     *
     * @return array{ok:int,fail:int,skipped:bool,error:?string}
     */
    public static function checkAll(): array
    {
        if (! self::configured()) {
            return ['ok' => 0, 'fail' => 0, 'skipped' => true, 'error' => null];
        }

        $ok = 0;
        $fail = 0;
        $error = null;

        foreach (Post::where('status', 'published')->get() as $post) {
            [$success, $coverage, $message] = self::inspect(self::urlFor($post));

            if ($success) {
                $post->google_coverage = $coverage;
                $post->google_checked_at = now();
                $post->saveQuietly();
                $ok++;
            } else {
                $fail++;
                $error ??= $message;
            }
        }

        return ['ok' => $ok, 'fail' => $fail, 'skipped' => false, 'error' => $error];
    }

    /**
     * Re-check the Google index status of articles that are NOT yet indexed and
     * whose last check is older than $staleHours (or never checked). Already
     * indexed articles are skipped to save API quota. Designed to be called
     * every minute from the cron with a small $limit, so it spreads a daily
     * refresh across many runs without any long request.
     *
     * @return array{ok:int,fail:int,skipped:bool}
     */
    public static function checkStale(int $limit = 5, int $staleHours = 20): array
    {
        if (! self::enabled()) {
            return ['ok' => 0, 'fail' => 0, 'skipped' => true];
        }

        $threshold = now()->subHours($staleHours);

        $posts = Post::where('status', 'published')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('google_checked_at')->orWhere('google_checked_at', '<', $threshold);
            })
            ->orderByRaw('google_checked_at IS NOT NULL, google_checked_at ASC')
            ->get()
            ->filter(fn ($p) => self::classify($p->google_coverage) !== 'indexed')
            ->take($limit);

        $ok = 0;
        $fail = 0;

        foreach ($posts as $post) {
            [$success, $coverage] = self::inspect(self::urlFor($post));

            if ($success) {
                $post->google_coverage = $coverage;
                $post->google_checked_at = now();
                $post->saveQuietly();
                $ok++;
            } else {
                $fail++;
            }
        }

        return ['ok' => $ok, 'fail' => $fail, 'skipped' => false];
    }

    /** Submit only published articles that are NOT currently indexed (per last check). */
    public static function submitUnindexed(): array
    {
        if (! self::enabled()) {
            return ['ok' => 0, 'fail' => 0, 'skipped' => true];
        }

        $posts = Post::where('status', 'published')->get()
            ->filter(fn ($p) => self::classify($p->google_coverage) !== 'indexed');

        return self::submitCollection($posts);
    }

    /**
     * Submit published articles that have never been sent (or were edited
     * since). Called automatically after articles publish. Scheduled posts are
     * excluded by the status filter, so future-scheduled articles are only sent
     * once they actually go live.
     *
     * @return array{ok:int,fail:int,skipped:bool}
     */
    public static function submitPending(int $limit = 10): array
    {
        if (! self::enabled()) {
            return ['ok' => 0, 'fail' => 0, 'skipped' => true];
        }

        $posts = Post::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('google_submitted_at')
                    ->orWhereColumn('updated_at', '>', 'google_submitted_at');
            })
            ->limit($limit)
            ->get();

        return self::submitCollection($posts);
    }

    /** Submit every published article (force resubmit). Used by the dashboard button. */
    public static function submitAll(): array
    {
        if (! self::enabled()) {
            return ['ok' => 0, 'fail' => 0, 'skipped' => true];
        }

        return self::submitCollection(Post::where('status', 'published')->get());
    }

    /** @param \Illuminate\Support\Collection<int,Post> $posts */
    protected static function submitCollection($posts): array
    {
        $ok = 0;
        $fail = 0;

        foreach ($posts as $post) {
            [$success, $message] = self::publish(self::urlFor($post));

            $post->google_submitted_at = now();
            $post->google_index_status = $success ? 'submitted' : ('error: ' . Str::limit($message, 150));
            $post->saveQuietly();

            $success ? $ok++ : $fail++;
        }

        return ['ok' => $ok, 'fail' => $fail, 'skipped' => false];
    }
}
