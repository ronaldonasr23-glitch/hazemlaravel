<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Http\Response;

/**
 * Wraps dynamically-rendered pages (the blog listing + article singles) in the
 * site's REAL captured theme chrome, so they match the rest of the site exactly:
 * the hello-elementor header/nav, footer, fonts and CSS are taken straight from
 * the captured home page of the matching language (English or Arabic/RTL), and
 * only the page-content region is replaced with our own markup.
 *
 * This is why a dynamic article looks identical to a captured page even though
 * the `settings` header/footer HTML was never seeded.
 */
class SiteShell
{
    /** language => captured home page that provides the chrome. */
    private const SHELL_PAGE = [
        'en' => '',
        'ar' => 'ar/home-ar',
    ];

    /** @var array<string,array> per-language extracted shell cache */
    private static array $cache = [];

    /**
     * Wrap already-rendered inner HTML in the site's captured chrome with the
     * given per-page SEO, and return a full-page response.
     */
    public static function render(string $language, string $inner, array $seo): Response
    {
        $shell = self::get($language);

        // Dynamic pages must not be stored by the edge/CDN cache, so changing
        // content (appointment slots, articles) always reflects immediately.
        return response()->view('dynamic.shell', [
            'head'      => self::head($language, $seo),
            'top'       => $shell['top'],
            'bottom'    => $shell['bottom'],
            'bodyClass' => $shell['bodyClass'],
            'lang'      => $shell['lang'],
            'dir'       => $shell['dir'],
            'content'   => $inner,
        ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * @return array{head:string,top:string,bottom:string,bodyClass:string,lang:string,dir:?string}
     */
    public static function get(string $language = 'en'): array
    {
        $language = $language === 'ar' ? 'ar' : 'en';
        if (isset(self::$cache[$language])) {
            return self::$cache[$language];
        }

        $path = self::SHELL_PAGE[$language];
        $page = Page::where('path', $path)->first() ?? Page::where('path', '')->first();

        $body = $page->content_html;
        [$top, $bottom] = self::splitAtPageContent($body);

        return self::$cache[$language] = [
            'head'      => $page->head_html,
            'top'       => $top,
            'bottom'    => $bottom,
            'bodyClass' => $page->body_class,
            'lang'      => $page->html_lang ?: ($language === 'ar' ? 'ar' : 'en-US'),
            'dir'       => $page->html_dir ?: ($language === 'ar' ? 'rtl' : null),
        ];
    }

    /**
     * Split the captured body around the Elementor page-content container
     * (`data-elementor-type="wp-page"`), returning [everything before it,
     * everything after it]. Our content goes in the gap.
     *
     * @return array{0:string,1:string}
     */
    private static function splitAtPageContent(string $body): array
    {
        $marker = strpos($body, 'data-elementor-type="wp-page"');
        if ($marker === false) {
            // Fallback: inject before the footer, else at the very end.
            $f = strpos($body, 'data-elementor-type="footer"');
            $f = $f === false ? strlen($body) : strrpos(substr($body, 0, $f), '<');

            return [substr($body, 0, $f), substr($body, $f)];
        }

        // Back up to the opening "<div" of the wp-page container.
        $open = strrpos(substr($body, 0, $marker), '<div');
        // Find its matching close by counting nested <div>/</div>.
        $end = self::matchingDivEnd($body, $open);

        return [substr($body, 0, $open), substr($body, $end)];
    }

    /** Offset just past the </div> that closes the <div> starting at $open. */
    private static function matchingDivEnd(string $html, int $open): int
    {
        $depth = 0;
        $len = strlen($html);
        $i = $open;
        while ($i < $len) {
            $lt = strpos($html, '<', $i);
            if ($lt === false) {
                break;
            }
            if (substr($html, $lt, 4) === '<div') {
                $depth++;
                $i = $lt + 4;
            } elseif (substr($html, $lt, 6) === '</div>') {
                $depth--;
                $i = $lt + 6;
                if ($depth === 0) {
                    return $i;
                }
            } else {
                $i = $lt + 1;
            }
        }

        return $len;
    }

    /**
     * Build the <head> for a dynamic page: keep the captured home head (fonts,
     * CSS, scripts) but strip the home's own SEO tags and inject this page's.
     *
     * @param array{title:string,description?:?string,canonical:string,image?:?string,og_type?:string,published_at?:?string,robots?:?string} $seo
     */
    public static function head(string $language, array $seo): string
    {
        $shell = self::get($language);
        $head = $shell['head'];

        // Strip the shell page's own SEO/meta so it doesn't duplicate/conflict.
        $strip = [
            '/<title\b[^>]*>.*?<\/title>/is',
            '/<meta\s+name="description"[^>]*>/is',
            '/<meta\s+name="robots"[^>]*>/is',
            '/<link\s+rel="canonical"[^>]*>/is',
            '/<meta\s+property="og:[^"]*"[^>]*>/is',
            '/<meta\s+name="twitter:[^"]*"[^>]*>/is',
            '/<meta\s+property="article:[^"]*"[^>]*>/is',
            '/<script\s+type="application\/ld\+json"[^>]*>.*?<\/script>/is',
            // Yoast comment markers
            '/<!--\s*This site is optimized with the Yoast.*?-->/is',
        ];
        $head = preg_replace($strip, '', $head);

        $e = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $title = $e($seo['title']);
        $desc = $e($seo['description'] ?? '');
        $canon = $e($seo['canonical']);
        $ogType = $e($seo['og_type'] ?? 'website');
        $image = $seo['image'] ?? null;

        $tags = "\n<title>{$title}</title>\n";
        if ($desc !== '') {
            $tags .= "<meta name=\"description\" content=\"{$desc}\">\n";
        }
        $tags .= "<meta name=\"robots\" content=\"" . $e($seo['robots'] ?? 'index, follow') . "\">\n";
        $tags .= "<link rel=\"canonical\" href=\"{$canon}\">\n";
        $tags .= "<meta property=\"og:locale\" content=\"" . ($language === 'ar' ? 'ar_AR' : 'en_US') . "\">\n";
        $tags .= "<meta property=\"og:type\" content=\"{$ogType}\">\n";
        $tags .= "<meta property=\"og:title\" content=\"{$title}\">\n";
        if ($desc !== '') {
            $tags .= "<meta property=\"og:description\" content=\"{$desc}\">\n";
        }
        $tags .= "<meta property=\"og:url\" content=\"{$canon}\">\n";
        if ($image) {
            $abs = $e(self::absolute($image));
            $tags .= "<meta property=\"og:image\" content=\"{$abs}\">\n";
            $tags .= "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
            $tags .= "<meta name=\"twitter:image\" content=\"{$abs}\">\n";
        }
        $tags .= "<meta name=\"twitter:title\" content=\"{$title}\">\n";
        if ($desc !== '') {
            $tags .= "<meta name=\"twitter:description\" content=\"{$desc}\">\n";
        }
        if (! empty($seo['published_at'])) {
            $tags .= "<meta property=\"article:published_time\" content=\"" . $e($seo['published_at']) . "\">\n";
        }

        // Insert right after the opening charset meta (or at the top of head).
        if (preg_match('/<meta\s+charset[^>]*>/i', $head, $m, PREG_OFFSET_CAPTURE)) {
            $at = $m[0][1] + strlen($m[0][0]);

            return substr($head, 0, $at) . $tags . substr($head, $at);
        }

        return $tags . $head;
    }

    private static function absolute(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return rtrim(config('app.url'), '/') . '/' . ltrim($url, '/');
    }
}
