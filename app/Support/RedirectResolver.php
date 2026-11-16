<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Support\Str;

/**
 * Figures out the correct current page for an old (WordPress) URL, so the
 * importer can build 301 redirects automatically.
 */
class RedirectResolver
{
    /** Known static page slugs that map to themselves. */
    protected array $pageSlugs = [];
    protected array $postSlugs = [];
    protected array $serviceSlugs = [];
    protected array $portfolioSlugs = [];
    protected array $teamSlugs = [];

    public function __construct()
    {
        $this->pageSlugs = Page::pluck('slug')->all();
        $this->postSlugs = Post::pluck('slug')->all();
        $this->serviceSlugs = Service::pluck('slug')->all();
        $this->portfolioSlugs = Portfolio::pluck('slug')->all();
        $this->teamSlugs = TeamMember::pluck('slug')->all();
    }

    /**
     * @return array{from:string,to:string}|null  null = skip (already correct / ignorable)
     */
    public function resolve(string $rawUrl): ?array
    {
        $from = $this->pathOf($rawUrl);
        if ($from === null || $from === '/') {
            return null;
        }

        // Working copy we simplify to find a target.
        $p = $from;
        $p = preg_replace('#^/index\.php(?=/|$)#', '', $p);   // strip /index.php
        $p = preg_replace('#/feed$#', '', $p);                 // strip trailing /feed
        $p = preg_replace('#/page/\d+$#', '', $p);             // strip pagination
        $p = $this->normalize($p);
        if ($p === '') {
            $p = '/';
        }

        $to = $this->targetFor($p);
        if ($to === null) {
            return null;
        }

        $to = $this->normalize($to);

        // Already pointing at the right place → no redirect needed.
        if ($to === $from) {
            return null;
        }

        return ['from' => $from, 'to' => $to];
    }

    protected function targetFor(string $p): ?string
    {
        if ($p === '/' || $p === '') {
            return '/';
        }

        $seg = array_values(array_filter(explode('/', trim($p, '/')), fn ($s) => $s !== ''));
        $first = $seg[0] ?? '';
        $last = end($seg) ?: '';

        // --- prefixed resources ---
        if ($first === 'service') {
            $slug = $last;
            return in_array($slug, $this->serviceSlugs, true) ? "/service/$slug" : '/service-3-2-2';
        }
        if ($first === 'portfolio') {
            if (count($seg) === 1) {
                return '/portfolio';
            }
            return in_array($last, $this->portfolioSlugs, true) ? "/portfolio/$last" : '/portfolio';
        }
        if ($first === 'category-portfolio') {
            return '/portfolio';
        }
        if ($first === 'team') {
            return in_array($last, $this->teamSlugs, true) ? "/team/$last" : '/volunteer';
        }
        // Removed content types → closest sensible page
        if (in_array($first, ['gallery', 'galleries'], true)) {
            return '/';
        }
        if (in_array($first, ['event', 'events', 'tribe_events'], true)) {
            return '/news';
        }
        if (in_array($first, ['category', 'tag'], true)) {
            return '/news';
        }
        if (in_array($first, ['gva_header', 'footer', 'gva_team'], true)) {
            return $first === 'gva_team' && in_array($last, $this->teamSlugs, true) ? "/team/$last" : '/';
        }
        if (in_array($first, ['cart', 'checkout', 'my-account', 'comments', 'wp-login', 'author'], true)) {
            return '/';
        }

        // --- single segment (page or post or root-level content) ---
        if (count($seg) === 1) {
            // Known static page slug
            if (in_array($first, $this->pageSlugs, true)) {
                return "/$first";
            }
            // Post (strip duplicate numeric suffix like -2)
            $base = preg_replace('/-\d+$/', '', $first);
            if (in_array($first, $this->postSlugs, true)) {
                return "/$first";
            }
            if ($base !== $first && in_array($base, $this->postSlugs, true)) {
                return "/$base";
            }
            // A root-level slug that is really a service / portfolio / team
            if (in_array($first, $this->serviceSlugs, true)) {
                return "/service/$first";
            }
            if (in_array($base, $this->serviceSlugs, true)) {
                return "/service/$base";
            }
            if (in_array($first, $this->portfolioSlugs, true)) {
                return "/portfolio/$first";
            }
            if (in_array($first, $this->teamSlugs, true)) {
                return "/team/$first";
            }
            // Unknown article → send to the news index (keeps SEO value in-section)
            return '/news';
        }

        // Anything else unknown → home
        return '/';
    }

    /** Extract a clean path from a full URL or a bare path. */
    protected function pathOf(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        // strip scheme+host if present
        $path = preg_match('#^https?://#i', $raw) ? (parse_url($raw, PHP_URL_PATH) ?? '/') : $raw;
        // drop query/fragment
        $path = explode('?', $path, 2)[0];
        $path = explode('#', $path, 2)[0];
        $path = rawurldecode($path);

        return $this->normalize($path);
    }

    /** Leading slash, no trailing slash (except root), collapse slashes. */
    protected function normalize(string $path): string
    {
        $path = '/' . ltrim(trim($path), '/');
        $path = preg_replace('#/+#', '/', $path);
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }
}
