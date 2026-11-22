<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import articles straight from the live WordPress source DB into the native
 * `posts` table, so the chem living-blog engine (BlogController listing,
 * ArticlePublisher scheduling, articles:publish-due) drives them instead of the
 * frozen HTML capture.
 *
 *  - Published WP posts  -> status "published" (render now at their exact URL)
 *  - Scheduled WP posts  -> status "scheduled" + future published_at (show in
 *    the admin publisher; go live automatically on their date, like WP-Cron)
 *
 * Each article keeps its EXACT original permalink (path + language + RTL) so
 * nothing 404s. Yoast SEO (title/description/focus keyphrase/OG/Twitter/schema)
 * is copied into seo_meta. Re-running is idempotent (keyed by the WP id).
 */
class ImportWpArticles extends Command
{
    protected $signature = 'wp:import-articles
        {--prune : Remove captured wp_type=post rows from the pages table after import (so articles render dynamically)}';

    protected $description = 'Import published + scheduled articles from the WordPress source DB into the posts table';

    private int $created = 0;
    private int $updated = 0;
    private int $scheduled = 0;

    public function handle(): int
    {
        $wp = DB::connection('wp');
        $authorId = User::query()->orderBy('id')->value('id');

        // EXACT permalink per post id, taken from the inventory that generated the
        // sitemap/Search Console URLs (get_permalink). WordPress' post_name can
        // differ from the real permalink slug for Arabic posts, so this is the
        // only reliable source for a zero-404 path.
        $urlMap = [];
        $file = database_path('seeders/raw/urls.json');
        if (is_file($file)) {
            foreach ((json_decode(file_get_contents($file), true)['items'] ?? []) as $it) {
                if (! empty($it['id']) && isset($it['url'])) {
                    $urlMap[(int) $it['id']] = trim(rawurldecode(parse_url($it['url'], PHP_URL_PATH) ?? ''), '/');
                }
            }
        }

        $rows = $wp->table('posts')
            ->where('post_type', 'post')
            ->whereIn('post_status', ['publish', 'future'])
            ->orderBy('post_date')
            ->get();

        $this->info('Importing ' . $rows->count() . ' articles from the WordPress source ...');
        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        foreach ($rows as $row) {
            $this->importOne($wp, $row, $authorId, $urlMap);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->info("Created: {$this->created}  Updated: {$this->updated}  (of which scheduled: {$this->scheduled})");

        if ($this->option('prune')) {
            $n = Page::where('wp_type', 'post')->delete();
            $this->warn("Pruned {$n} captured post pages (they now render dynamically via the Post model).");
        }

        return self::SUCCESS;
    }

    private function importOne($wp, object $row, ?int $authorId, array $urlMap): void
    {
        $status = $row->post_status === 'future' ? 'scheduled' : 'published';

        // Language is taken from Polylang (authoritative): the capture mislabeled
        // root-level Arabic posts as "en", so we don't trust the page's language.
        // The PATH, though, must be the exact captured permalink (zero-404), so we
        // reuse the captured page's path when we have it (published posts).
        $language = $this->language($wp, $row->ID, $row->post_name);

        // Direction follows the ACTUAL text, not the Polylang label: the source
        // filed some Arabic-content articles under the no-prefix (English) tree,
        // so they're language=en but must still render RTL for readability.
        $isArabicText = (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $row->post_title);
        $dir = ($language === 'ar' || $isArabicText) ? 'rtl' : 'ltr';

        // Exact permalink path from the published-URL inventory (zero-404). Only
        // scheduled posts — absent from that inventory because they weren't public
        // when it was built — fall back to the (clean, Latin) decoded post_name.
        $slug = rawurldecode($row->post_name);
        $path = $urlMap[$row->ID] ?? (($language === 'ar' ? 'ar/' : '') . $slug);

        $content = $this->clean((string) $row->post_content);
        $meta = $this->meta($wp, $row->ID);

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
        $words = $plain === '' ? 0 : count(preg_split('/\s+/', $plain));

        $excerpt = $this->clean((string) ($row->post_excerpt ?: ''));
        if ($excerpt === '') {
            $excerpt = $meta['_yoast_wpseo_metadesc'] ?? Str::limit($plain, 200);
        }

        $post = Post::updateOrCreate(
            ['wp_id' => $row->ID],
            [
                'title'          => mb_substr($this->clean((string) $row->post_title), 0, 250),
                'slug'           => $slug,
                'path'           => $path,
                'language'       => $language,
                'html_dir'       => $dir,
                'excerpt'        => Str::limit($this->clean($excerpt), 300),
                'content'        => $content,
                'featured_image' => $this->thumbnail($wp, $row->ID),
                'author_id'      => $authorId,
                'status'         => $status,
                'reading_time'   => max(1, (int) ceil($words / 200)),
                'published_at'   => $row->post_date,
            ]
        );

        $this->saveSeo($post, $meta);

        $post->wasRecentlyCreated ? $this->created++ : $this->updated++;
        if ($status === 'scheduled') {
            $this->scheduled++;
        }
    }

    /** Polylang language term slug (en/ar) for a post, with an Arabic-text fallback. */
    private function language($wp, int $postId, string $slug): string
    {
        $lang = $wp->table('term_relationships as tr')
            ->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->join('terms as t', 't.term_id', '=', 'tt.term_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', 'language')
            ->value('t.slug');

        if ($lang) {
            return str_starts_with($lang, 'ar') ? 'ar' : 'en';
        }

        return preg_match('/[\x{0600}-\x{06FF}]/u', rawurldecode($slug)) ? 'ar' : 'en';
    }

    /** All postmeta for a post as key => value. */
    private function meta($wp, int $postId): array
    {
        return $wp->table('postmeta')
            ->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($v) => $this->clean((string) $v))
            ->all();
    }

    /** Resolve the featured image to a public /wp-content/uploads/... path. */
    private function thumbnail($wp, int $postId): ?string
    {
        $thumbId = $wp->table('postmeta')
            ->where('post_id', $postId)->where('meta_key', '_thumbnail_id')
            ->value('meta_value');
        if (! $thumbId) {
            return null;
        }

        $file = $wp->table('postmeta')
            ->where('post_id', $thumbId)->where('meta_key', '_wp_attached_file')
            ->value('meta_value');

        return $file ? '/wp-content/uploads/' . ltrim($file, '/') : null;
    }

    /** Map Yoast postmeta into the seo_meta row. */
    private function saveSeo(Post $post, array $meta): void
    {
        $map = [
            'seo_title'            => '_yoast_wpseo_title',
            'meta_description'     => '_yoast_wpseo_metadesc',
            'focus_keyphrase'      => '_yoast_wpseo_focuskw',
            'keyphrase_synonyms'   => '_yoast_wpseo_focuskw_synonyms',
            'og_title'             => '_yoast_wpseo_opengraph-title',
            'og_description'       => '_yoast_wpseo_opengraph-description',
            'twitter_title'        => '_yoast_wpseo_twitter-title',
            'twitter_description'  => '_yoast_wpseo_twitter-description',
            'schema_page_type'     => '_yoast_wpseo_schema_page_type',
            'schema_article_type'  => '_yoast_wpseo_schema_article_type',
        ];

        $seo = [];
        foreach ($map as $col => $key) {
            $val = trim((string) ($meta[$key] ?? ''));
            if ($val !== '') {
                // Yoast title templates use %%...%% snippet vars; drop the suffix.
                $val = trim(preg_replace('/\s*%%[^%]+%%\s*/', ' ', $val));
                $seo[$col] = $val !== '' ? $val : null;
            }
        }

        $seo['seo_title'] ??= $post->title;
        $seo['og_image'] = $seo['twitter_image'] = $post->featured_image;

        $post->seo()->updateOrCreate([], $seo);
    }

    /** Scrub invalid UTF-8 (the source has encoding issues) so it stores cleanly. */
    private function clean(string $s): string
    {
        if ($s === '') {
            return $s;
        }
        $c = @mb_scrub($s, 'UTF-8');

        return is_string($c) ? $c : $s;
    }
}
