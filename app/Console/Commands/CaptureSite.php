<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off (idempotent) capture of the live WordPress source site.
 *
 * Fetches the fully-rendered HTML of every original URL (pages, posts, cases,
 * medical_faq, taxonomy & author archives — both languages, full hierarchy) and
 * stores each one in the `pages` table keyed by its exact relative path, so the
 * Laravel replica serves every indexed URL 1:1 with zero 404s. The captured
 * <head> already carries the original Yoast SEO tags + Google Site Kit snippets,
 * so front-end SEO and analytics are preserved verbatim.
 */
class CaptureSite extends Command
{
    protected $signature = 'wp:capture
        {--fresh : Truncate the pages table before capturing}
        {--source= : Source Host header (defaults to WP_SOURCE_HOST)}
        {--ip=127.0.0.1 : IP/host the dev server actually listens on}';

    protected $description = 'Capture the live WordPress site into the pages table (faithful, zero-404 replica)';

    private string $sourceHost;
    private string $appHost;
    private int $ok = 0;
    private int $fail = 0;
    private array $failed = [];

    public function handle(): int
    {
        $this->sourceHost = $this->option('source') ?: env('WP_SOURCE_HOST', 'hazemfezary.test');
        $this->appHost    = parse_url(config('app.url'), PHP_URL_HOST) ?: 'hazemlaravel.test';
        $ip               = $this->option('ip');

        $file = database_path('seeders/raw/urls.json');
        if (! is_file($file)) {
            $this->error("Inventory not found: $file");
            return self::FAILURE;
        }
        $data = json_decode(file_get_contents($file), true);
        if (! $data) {
            $this->error('Could not decode urls.json');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::table('pages')->truncate();
            $this->warn('Truncated pages table.');
        }

        // Track which relative paths we have stored, to avoid duplicate work.
        $seen = [];

        // 1) Content items (pages/posts/cases/medical_faq) — rich metadata.
        $items = $data['items'] ?? [];
        $frontId = (int) ($data['front_page'] ?? 0);
        $this->info('Capturing ' . count($items) . ' content URLs from ' . $this->sourceHost . ' ...');
        $bar = $this->output->createProgressBar(count($items));
        $bar->start();
        foreach ($items as $it) {
            $path = $this->normalizePath($it['url']);
            $lang = $it['lang'] ?: ($this->looksArabic($path) ? 'ar' : 'en');
            $this->captureUrl($ip, $it['url'], [
                'path'         => $path,
                'language'     => $lang,
                'slug'         => $it['slug'] ?? null,
                'wp_type'      => $it['type'] ?? null,
                'wp_id'        => $it['id'] ?? null,
                'wp_parent_id' => $it['parent'] ?? null,
                'title'        => $it['title'] ?? $path,
                'menu_order'   => $it['menu_order'] ?? 0,
                'is_front'     => ((int) ($it['id'] ?? 0) === $frontId),
            ]);
            $seen[$path] = true;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        // 2) Any extra indexed URL from the live sitemap not already captured
        //    (taxonomy archives, author archive, etc.).
        $smFile = database_path('seeders/raw/sitemap_urls.txt');
        if (is_file($smFile)) {
            $extra = [];
            foreach (preg_split('/\R/', trim(file_get_contents($smFile))) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $p = $this->normalizePath(parse_url($line, PHP_URL_PATH) ?? '/');
                if (! isset($seen[$p]) && ! array_key_exists($p, $extra)) {
                    $extra[$p] = parse_url($line, PHP_URL_PATH) ?? '/';
                }
            }
            if ($extra) {
                $this->info('Capturing ' . count($extra) . ' extra sitemap URLs (archives/terms) ...');
                foreach ($extra as $p => $urlPath) {
                    $this->captureUrl($ip, $urlPath, [
                        'path'       => $p,
                        'language'   => $this->looksArabic($p) ? 'ar' : 'en',
                        'slug'       => null,
                        'wp_type'    => 'archive',
                        'wp_id'      => null,
                        'wp_parent_id' => null,
                        'title'      => $p,
                        'menu_order' => 0,
                        'is_front'   => false,
                    ]);
                    $seen[$p] = true;
                }
            }
        }

        $this->newLine();
        $this->info("Captured OK: {$this->ok}   Failed: {$this->fail}");
        if ($this->failed) {
            $this->warn('Failed URLs:');
            foreach ($this->failed as $f) {
                $this->line('  - ' . $f);
            }
        }

        return self::SUCCESS;
    }

    private function captureUrl(string $ip, string $urlPath, array $meta): void
    {
        $html = $this->fetch($ip, $urlPath);
        if ($html === null || strlen($html) < 200) {
            $this->fail++;
            $this->failed[] = $urlPath . ' (empty/short)';
            return;
        }

        $parts = $this->splitHtml($html);
        if ($parts === null) {
            $this->fail++;
            $this->failed[] = $urlPath . ' (no <head>/<body>)';
            return;
        }

        $head = $this->rewriteHost($parts['head']);
        $body = $this->rewriteHost($parts['body']);

        Page::updateOrCreate(
            ['path' => $meta['is_front'] ? '' : $meta['path']],
            [
                'language'     => $meta['language'],
                'slug'         => $meta['slug'],
                'wp_type'      => $meta['wp_type'],
                'wp_id'        => $meta['wp_id'],
                'wp_parent_id' => $meta['wp_parent_id'],
                'title'        => mb_substr((string) $meta['title'], 0, 250),
                'body_class'   => mb_substr($parts['body_class'] ?? '', 0, 990),
                'html_lang'    => $parts['html_lang'] ?? null,
                'html_dir'     => $parts['html_dir'] ?? null,
                'head_html'    => $head,
                'content_html' => $body,
                'menu_order'   => (int) $meta['menu_order'],
                'is_published' => true,
            ]
        );
        $this->ok++;
    }

    private function fetch(string $ip, string $urlPath): ?string
    {
        $url = 'http://' . $ip . '/' . ltrim($urlPath, '/');
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_HTTPHEADER     => ['Host: ' . $this->sourceHost, 'Accept-Language: ' . ($this->looksArabic($urlPath) ? 'ar' : 'en')],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => 45,
                CURLOPT_ENCODING       => '', // accept gzip, curl decodes
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (capture) SiteReplica',
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body !== false && $code >= 200 && $code < 400) {
                return $body;
            }
            usleep(200000);
        }
        return null;
    }

    private function splitHtml(string $html): ?array
    {
        if (! preg_match('/<head\b[^>]*>(.*?)<\/head>/is', $html, $hm)) {
            return null;
        }
        if (! preg_match('/<body\b([^>]*)>(.*)<\/body>/is', $html, $bm)) {
            return null;
        }
        $bodyAttrs = $bm[1];
        $bodyInner = $bm[2];

        $bodyClass = '';
        if (preg_match('/\bclass\s*=\s*"([^"]*)"/i', $bodyAttrs, $cm)) {
            $bodyClass = $cm[1];
        } elseif (preg_match("/\bclass\s*=\s*'([^']*)'/i", $bodyAttrs, $cm)) {
            $bodyClass = $cm[1];
        }

        $htmlLang = null;
        $htmlDir  = null;
        if (preg_match('/<html\b([^>]*)>/i', $html, $hmm)) {
            if (preg_match('/\blang\s*=\s*["\']([^"\']*)["\']/i', $hmm[1], $lm)) {
                $htmlLang = $lm[1];
            }
            if (preg_match('/\bdir\s*=\s*["\']([^"\']*)["\']/i', $hmm[1], $dm)) {
                $htmlDir = $dm[1];
            }
        }

        return [
            'head'       => trim($hm[1]),
            'body'       => trim($bodyInner),
            'body_class' => trim($bodyClass),
            'html_lang'  => $htmlLang,
            'html_dir'   => $htmlDir,
        ];
    }

    /** Rewrite the source host to the replica host (absolute + protocol-relative). */
    private function rewriteHost(string $s): string
    {
        $src = preg_quote($this->sourceHost, '#');
        return preg_replace(
            ['#https?://' . $src . '#i', '#//' . $src . '#i'],
            ['http://' . $this->appHost, '//' . $this->appHost],
            $s
        );
    }

    private function normalizePath(string $url): string
    {
        $p = parse_url($url, PHP_URL_PATH);
        $p = $p === null ? '' : $p;
        return trim(rawurldecode($p), '/');
    }

    private function looksArabic(string $path): bool
    {
        return str_starts_with(ltrim($path, '/'), 'ar/') || ltrim($path, '/') === 'ar';
    }
}
