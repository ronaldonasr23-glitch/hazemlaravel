<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;

/**
 * Reproduce WordPress' automatic canonical redirects so that any old/short URL
 * Google may still hold (e.g. the bare page slug) 301s to its real hierarchical
 * URL instead of 404ing. Yoast/redirect-manager had no custom redirects on the
 * source site, so these WP canonicals + the Polylang language-home are all we
 * need. Idempotent.
 */
class BuildRedirects extends Command
{
    protected $signature = 'wp:redirects {--fresh : delete generated redirects first}';
    protected $description = 'Generate WordPress-style canonical 301 redirects from the URL inventory';

    public function handle(): int
    {
        $file = database_path('seeders/raw/urls.json');
        if (! is_file($file)) {
            $this->error("Inventory not found: $file");
            return self::FAILURE;
        }
        $data = json_decode(file_get_contents($file), true);
        $items = $data['items'] ?? [];

        if ($this->option('fresh')) {
            Redirect::where('source', 'wp-canonical')->delete();
        }

        // Count slug occurrences so we only add a bare-slug redirect when it is
        // unambiguous (a slug shared by several pages can't map to one target).
        $slugCount = [];
        foreach ($items as $it) {
            if (($it['type'] ?? '') === 'page' && ! empty($it['slug'])) {
                $slugCount[$it['slug']] = ($slugCount[$it['slug']] ?? 0) + 1;
            }
        }

        $made = 0;
        $skipped = 0;
        foreach ($items as $it) {
            if (($it['type'] ?? '') !== 'page' || empty($it['slug'])) {
                continue;
            }
            $full = '/' . trim(rawurldecode(parse_url($it['url'], PHP_URL_PATH) ?? ''), '/');
            $bare = '/' . $it['slug'];

            // Only when the real URL is hierarchical (bare != full) and unique.
            if ($bare === $full || ($slugCount[$it['slug']] ?? 0) !== 1) {
                $skipped++;
                continue;
            }

            Redirect::updateOrCreate(
                ['from_path' => $bare],
                ['to_path' => $full, 'status_code' => 301, 'is_active' => true, 'source' => 'wp-canonical']
            );
            $made++;
        }

        // Polylang language home: /ar -> /ar/home-ar (whatever the Arabic front is)
        $arFront = collect($items)->first(fn ($i) => ($i['lang'] ?? '') === 'ar'
            && in_array($i['slug'] ?? '', ['home-ar', 'home', 'الرئيسية'], true));
        if ($arFront) {
            $to = '/' . trim(rawurldecode(parse_url($arFront['url'], PHP_URL_PATH) ?? ''), '/');
            if ($to !== '/ar') {
                Redirect::updateOrCreate(
                    ['from_path' => '/ar'],
                    ['to_path' => $to, 'status_code' => 301, 'is_active' => true, 'source' => 'wp-canonical']
                );
                $made++;
            }
        }

        $this->info("Redirects created/updated: $made   (skipped $skipped non-hierarchical/ambiguous)");
        return self::SUCCESS;
    }
}
