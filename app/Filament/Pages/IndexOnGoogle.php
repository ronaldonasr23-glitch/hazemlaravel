<?php

namespace App\Filament\Pages;

use App\Models\Post;
use App\Models\Setting;
use App\Support\GoogleIndexing;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IndexOnGoogle extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'Index on Google';

    protected static ?string $title = 'Index articles on Google';

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.pages.index-on-google';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('manage_posts');
    }

    /** Public site base URL (never the local dev host). */
    public function base(): string
    {
        $site = rtrim((string) config('app.url'), '/');
        $host = parse_url($site, PHP_URL_HOST) ?: $site;

        if ($site === '' || preg_match('#(localhost|127\.0\.0\.1|\.test)$#i', $host)) {
            $site = 'https://chemgrowgas.com';
        }

        return $site;
    }

    /** Search Console property id used for the inspection deep links. */
    public function property(): string
    {
        return Setting::get('gsc_property') ?: ('sc-domain:' . preg_replace('#^https?://#', '', $this->base()));
    }

    /** A service account is saved (needed for the live check / submit). */
    public function indexingConfigured(): bool
    {
        return GoogleIndexing::configured();
    }

    public function apiEnabled(): bool
    {
        return GoogleIndexing::enabled();
    }

    public function totalCount(): int
    {
        return Post::where('status', 'published')->count();
    }

    /** True once at least one article has been checked. */
    public function anyChecked(): bool
    {
        return Post::where('status', 'published')->whereNotNull('google_checked_at')->exists();
    }

    /** Counts for the summary line. */
    public function summary(): array
    {
        $indexed = 0;
        $notIndexed = 0;
        $unknown = 0;
        $unchecked = 0;

        foreach (Post::where('status', 'published')->get(['google_coverage', 'google_checked_at']) as $p) {
            if (! $p->google_checked_at) {
                $unchecked++;

                continue;
            }

            match (GoogleIndexing::classify($p->google_coverage)) {
                'indexed'     => $indexed++,
                'not_indexed' => $notIndexed++,
                default       => $unknown++,
            };
        }

        return compact('indexed', 'notIndexed', 'unknown', 'unchecked');
    }

    /** All published articles with their current index/submit state. */
    public function articles(): Collection
    {
        $base     = $this->base();
        $property = $this->property();

        return Post::where('status', 'published')
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'slug', 'path', 'google_submitted_at', 'google_index_status', 'google_coverage', 'google_checked_at'])
            ->map(function ($p) use ($base, $property) {
                // Full path (with /ar/ for Arabic) + trailing slash = the canonical URL.
                $url = $base . '/' . trim((string) ($p->path ?: $p->slug), '/') . '/';

                return [
                    'id'        => $p->id,
                    'title'     => $p->title,
                    'url'       => $url,
                    'inspect'   => 'https://search.google.com/search-console/inspect?resource_id='
                        . rawurlencode($property) . '&id=' . rawurlencode($url),
                    'state'     => $p->google_checked_at ? GoogleIndexing::classify($p->google_coverage) : '',
                    'coverage'  => $p->google_coverage,
                    'submitted' => $p->google_submitted_at !== null
                        && str_starts_with((string) $p->google_index_status, 'submitted'),
                ];
            })
            ->values();
    }

    /**
     * STEP 1 — inspect ONE article's live Google index status and save it.
     * Called sequentially from JS so the user watches each result appear.
     */
    public function checkOne(int $id): array
    {
        $post = Post::where('status', 'published')->find($id);

        if (! $post) {
            return ['id' => $id, 'state' => 'unknown', 'label' => '—', 'coverage' => null];
        }

        [$ok, $coverage, $message] = GoogleIndexing::inspect(GoogleIndexing::urlFor($post));

        if (! $ok) {
            return ['id' => $id, 'state' => 'error', 'label' => 'Error', 'coverage' => $message];
        }

        $post->google_coverage = $coverage;
        $post->google_checked_at = now();
        $post->saveQuietly();

        $state = GoogleIndexing::classify($coverage);
        $labels = ['indexed' => 'Indexed ✓', 'not_indexed' => 'Not indexed', 'unknown' => 'Unknown'];

        return ['id' => $id, 'state' => $state, 'label' => $labels[$state] ?? 'Unknown', 'coverage' => $coverage];
    }

    /**
     * STEP 2 — submit ONE article to the Google Indexing API.
     * Called sequentially from JS for only the not-indexed articles.
     */
    public function submitOne(int $id): array
    {
        $post = Post::where('status', 'published')->find($id);

        if (! $post) {
            return ['id' => $id, 'ok' => false, 'message' => 'not found'];
        }

        [$ok, $message] = GoogleIndexing::publish(GoogleIndexing::urlFor($post));

        $post->google_submitted_at = now();
        $post->google_index_status = $ok ? 'submitted' : ('error: ' . Str::limit($message, 120));
        $post->saveQuietly();

        return ['id' => $id, 'ok' => $ok, 'message' => $ok ? 'submitted' : $message];
    }
}
