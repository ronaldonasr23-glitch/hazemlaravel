<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\SeoMeta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Seo
{
    /**
     * Build the full <head> inner markup for a dynamic page:
     * SEO meta tags + schema + all theme asset links.
     */
    public static function head(array $opts = []): string
    {
        return static::metaTags($opts) . "\n" . static::themeAssets();
    }

    /**
     * Yoast-style meta tags from a SeoMeta model (or plain options).
     */
    public static function metaTags(array $o = []): string
    {
        $seo = $o['seo'] ?? null;
        $siteName = Setting::get('site_name', config('app.name'));

        $title = $o['title']
            ?? ($seo?->seo_title)
            ?? ($o['fallback_title'] ?? $siteName);
        $title = static::applyVars($title, $o, $siteName);

        $desc  = $seo?->meta_description ?? ($o['description'] ?? null);
        $url   = $o['url'] ?? request()->url();
        $image = $seo?->og_image ?? ($o['image'] ?? null);
        if ($image && Str::startsWith($image, '/')) {
            $image = url($image);
        }

        $robots = [];
        if ($seo?->noindex) { $robots[] = 'noindex'; } else { $robots[] = 'index'; }
        if ($seo?->nofollow) { $robots[] = 'nofollow'; } else { $robots[] = 'follow'; }
        $robots[] = 'max-image-preview:large';

        $ogTitle = $seo?->og_title ?: $title;
        $ogDesc  = $seo?->og_description ?: $desc;
        $twTitle = $seo?->twitter_title ?: $title;
        $twDesc  = $seo?->twitter_description ?: $desc;
        $canonical = $seo?->canonical_url ?: $url;

        $e = fn ($s) => e($s ?? '');
        $out = [];
        $out[] = '<title>' . $e($title) . '</title>';
        if ($desc) { $out[] = '<meta name="description" content="' . $e($desc) . '" />'; }
        $out[] = '<meta name="robots" content="' . implode(', ', $robots) . '" />';
        $out[] = '<link rel="canonical" href="' . $e($canonical) . '" />';
        $out[] = '<meta property="og:locale" content="en_US" />';
        $out[] = '<meta property="og:type" content="' . ($o['og_type'] ?? 'website') . '" />';
        $out[] = '<meta property="og:title" content="' . $e($ogTitle) . '" />';
        if ($ogDesc) { $out[] = '<meta property="og:description" content="' . $e($ogDesc) . '" />'; }
        $out[] = '<meta property="og:url" content="' . $e($url) . '" />';
        $out[] = '<meta property="og:site_name" content="' . $e($siteName) . '" />';
        if ($image) { $out[] = '<meta property="og:image" content="' . $e($image) . '" />'; }
        $out[] = '<meta name="twitter:card" content="summary_large_image" />';
        $out[] = '<meta name="twitter:title" content="' . $e($twTitle) . '" />';
        if ($twDesc) { $out[] = '<meta name="twitter:description" content="' . $e($twDesc) . '" />'; }
        if ($image) { $out[] = '<meta name="twitter:image" content="' . $e($image) . '" />'; }

        // JSON-LD schema
        $out[] = static::schema($o, $title, $desc, $url, $image, $siteName);

        // Google verification + analytics injected site-wide
        $out[] = static::googleTags();

        return implode("\n", $out);
    }

    protected static function schema(array $o, $title, $desc, $url, $image, $siteName): string
    {
        $seo = $o['seo'] ?? null;
        $graph = [[
            '@type' => 'WebSite',
            '@id'   => url('/') . '/#website',
            'url'   => url('/'),
            'name'  => $siteName,
        ]];
        $type = $o['schema_type'] ?? ($seo?->schema_page_type ?? 'WebPage');
        $node = [
            '@type' => $type,
            'url'   => $url,
            'name'  => $title,
            'isPartOf' => ['@id' => url('/') . '/#website'],
        ];
        if ($desc) { $node['description'] = $desc; }
        if ($image) { $node['primaryImageOfPage'] = $image; }
        if (($o['og_type'] ?? null) === 'article') {
            $node = array_merge($node, [
                '@type' => $seo?->schema_article_type ?: 'Article',
                'headline' => $title,
                'datePublished' => $o['published_at'] ?? null,
                'author' => ['@type' => 'Organization', 'name' => $siteName],
            ]);
        }
        $graph[] = $node;

        return '<script type="application/ld+json">' .
            json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) .
            '</script>';
    }

    public static function googleTags(): string
    {
        $out = [];
        if ($v = Setting::get('google_site_verification')) {
            $out[] = '<meta name="google-site-verification" content="' . e($v) . '" />';
        }
        if ($ga = Setting::get('ga4_measurement_id')) {
            $out[] = "<script async src=\"https://www.googletagmanager.com/gtag/js?id=" . e($ga) . "\"></script>";
            $out[] = "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . e($ga) . "');</script>";
        }
        if ($gtm = Setting::get('gtm_id')) {
            $out[] = "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . e($gtm) . "');</script>";
        }
        if ($ads = Setting::get('google_ads_tag')) {
            $out[] = "<script async src=\"https://www.googletagmanager.com/gtag/js?id=" . e($ads) . "\"></script>";
            $out[] = "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . e($ads) . "');</script>";
        }

        return implode("\n", $out);
    }

    /**
     * Theme stylesheet/script links (shared across dynamic pages),
     * extracted once from the captured single-post head.
     */
    public static function themeAssets(): string
    {
        return Cache::rememberForever('theme_assets_head', function () {
            $head = Setting::get('tpl_post_head', '');
            // keep only <link ... stylesheet>, <style>, and <script src> tags
            preg_match_all('/<link[^>]+rel=["\']stylesheet["\'][^>]*>/i', $head, $links);
            preg_match_all('/<style[^>]*>.*?<\/style>/is', $head, $styles);
            preg_match_all('/<script[^>]+src=[^>]+><\/script>/i', $head, $scripts);

            return implode("\n", array_merge($links[0], $scripts[0], $styles[0]));
        });
    }

    protected static function applyVars(string $s, array $o, string $siteName): string
    {
        return strtr($s, [
            '%%title%%'    => $o['raw_title'] ?? '',
            '%%sitename%%' => $siteName,
            '%%sep%%'      => '-',
            '%%page%%'     => '',
        ]);
    }
}
