<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    protected $wp;

    public function run(): void
    {
        config(['database.connections.wp' => [
            'driver'    => 'mysql',
            'host'      => env('WP_DB_HOST', '127.0.0.1'),
            'port'      => env('WP_DB_PORT', '3306'),
            'database'  => env('WP_DB_DATABASE', 'chemgrowgas'),
            'username'  => env('WP_DB_USERNAME', 'root'),
            'password'  => env('WP_DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]]);

        $this->wp = DB::connection('wp');

        $this->importPosts();
        $this->importServices();
        $this->importTeam();
        $this->importPortfolios();

        $this->command->info('Content import complete.');
    }

    /* ---------- helpers ---------- */

    protected function meta(int $postId): array
    {
        return $this->wp->table('wp_postmeta')
            ->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')
            ->toArray();
    }

    protected function featuredImage(array $meta): ?string
    {
        $thumbId = $meta['_thumbnail_id'] ?? null;
        if (! $thumbId) {
            return null;
        }
        $file = $this->wp->table('wp_postmeta')
            ->where('post_id', $thumbId)
            ->where('meta_key', '_wp_attached_file')
            ->value('meta_value');

        return $file ? '/wp-content/uploads/' . $file : null;
    }

    protected function attach_seo($model, array $meta): void
    {
        $readingTime = (int) ($meta['_yoast_wpseo_estimated-reading-time-minutes'] ?? 0);

        $model->seo()->updateOrCreate([], [
            'seo_title'           => $meta['_yoast_wpseo_title'] ?? null,
            'meta_description'    => $meta['_yoast_wpseo_metadesc'] ?? null,
            'focus_keyphrase'     => $meta['_yoast_wpseo_focuskw'] ?? null,
            'keyphrase_synonyms'  => $meta['_yoast_wpseo_focuskw_synonyms'] ?? null,
            'canonical_url'       => $meta['_yoast_wpseo_canonical'] ?? null,
            'og_title'            => $meta['_yoast_wpseo_opengraph-title'] ?? null,
            'og_description'      => $meta['_yoast_wpseo_opengraph-description'] ?? null,
            'og_image'            => $meta['_yoast_wpseo_opengraph-image'] ?? null,
            'twitter_title'       => $meta['_yoast_wpseo_twitter-title'] ?? null,
            'twitter_description' => $meta['_yoast_wpseo_twitter-description'] ?? null,
            'schema_page_type'    => $meta['_yoast_wpseo_schema_page_type'] ?? 'WebPage',
            'schema_article_type' => $meta['_yoast_wpseo_schema_article_type'] ?? 'Article',
            'is_cornerstone'      => ! empty($meta['_yoast_wpseo_is_cornerstone']),
            'noindex'             => (($meta['_yoast_wpseo_meta-robots-noindex'] ?? '0') === '1'),
            'seo_score'           => (int) ($meta['_yoast_wpseo_linkdex'] ?? 0),
            'readability_score'   => (int) ($meta['_yoast_wpseo_content_score'] ?? 0),
            'reading_time'        => $readingTime,
        ]);
    }

    protected function uniqueSlug(string $base, string $table): string
    {
        $slug = Str::slug($base) ?: Str::random(8);
        $orig = $slug;
        $i = 2;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $orig . '-' . $i++;
        }

        return $slug;
    }

    protected function wpPosts(string $type)
    {
        return $this->wp->table('wp_posts')
            ->where('post_type', $type)
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get();
    }

    /* ---------- importers ---------- */

    protected function importPosts(): void
    {
        $seenTitles = [];
        foreach ($this->wpPosts('post') as $p) {
            $key = Str::lower(trim($p->post_title));
            if (isset($seenTitles[$key])) {
                continue; // dedup duplicated articles
            }
            $seenTitles[$key] = true;

            $meta = $this->meta($p->ID);
            $slug = $p->post_name ?: Str::slug($p->post_title);

            $post = Post::updateOrCreate(['slug' => $slug], [
                'title'         => $p->post_title,
                'excerpt'       => $p->post_excerpt ?: Str::limit(strip_tags($p->post_content), 160),
                'content'       => $p->post_content,
                'featured_image' => $this->featuredImage($meta),
                'status'        => 'published',
                'reading_time'  => (int) ($meta['_yoast_wpseo_estimated-reading-time-minutes'] ?? 0),
                'published_at'  => $p->post_date,
            ]);
            $this->attach_seo($post, $meta);
        }
        $this->command->info('Posts: ' . Post::count());
    }

    protected function importServices(): void
    {
        // Pool of real industrial images (from imported posts) to use as a
        // fallback for services that have no featured image in WordPress.
        $pool = Post::query()->whereNotNull('featured_image')->where('featured_image', '!=', '')
            ->orderBy('id')->pluck('featured_image')->values();
        $i = 0;

        foreach ($this->wpPosts('service') as $p) {
            $meta = $this->meta($p->ID);
            $slug = $p->post_name ?: $this->uniqueSlug($p->post_title, 'services');

            $image = $this->featuredImage($meta);
            if (! $image && $pool->isNotEmpty()) {
                $image = $pool[$i % $pool->count()];
                $i++;
            }

            $svc = Service::updateOrCreate(['slug' => $slug], [
                'title'          => $p->post_title,
                'excerpt'        => $p->post_excerpt ?: Str::limit(strip_tags($p->post_content), 160),
                'content'        => $p->post_content,
                'featured_image' => $image,
                'menu_order'     => $p->menu_order,
                'status'         => 'published',
            ]);
            $this->attach_seo($svc, $meta);
        }
        $this->command->info('Services: ' . Service::count());
    }

    protected function importTeam(): void
    {
        foreach ($this->wpPosts('gva_team') as $p) {
            $meta = $this->meta($p->ID);
            $slug = $p->post_name ?: $this->uniqueSlug($p->post_title, 'team_members');

            TeamMember::updateOrCreate(['slug' => $slug], [
                'name'       => $p->post_title,
                'position'   => $meta['gva_team_job'] ?? ($meta['job'] ?? null),
                'bio'        => $p->post_content,
                'photo'      => $this->featuredImage($meta),
                'socials'    => [
                    'facebook'  => $meta['gva_team_facebook'] ?? null,
                    'twitter'   => $meta['gva_team_twitter'] ?? null,
                    'linkedin'  => $meta['gva_team_linkedin'] ?? null,
                    'instagram' => $meta['gva_team_instagram'] ?? null,
                ],
                'menu_order' => $p->menu_order,
            ]);
        }
        $this->command->info('Team: ' . TeamMember::count());
    }

    protected function importPortfolios(): void
    {
        foreach ($this->wpPosts('portfolio') as $p) {
            $meta = $this->meta($p->ID);
            $slug = $p->post_name ?: $this->uniqueSlug($p->post_title, 'portfolios');

            $pf = Portfolio::updateOrCreate(['slug' => $slug], [
                'title'          => $p->post_title,
                'content'        => $p->post_content,
                'featured_image' => $this->featuredImage($meta),
                'menu_order'     => $p->menu_order,
                'status'         => 'published',
            ]);
            $this->attach_seo($pf, $meta);
        }
        $this->command->info('Portfolios: ' . Portfolio::count());
    }
}
