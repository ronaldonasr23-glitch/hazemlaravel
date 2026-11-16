<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Response;

/**
 * XML sitemaps that mirror the original Yoast layout: a sitemap index pointing
 * at per-type sub-sitemaps (page / post / cases / medical_faq / category /
 * faq_topic / author), each built from the captured `pages` table.
 */
class SitemapController extends Controller
{
    /** Yoast-style groups mapped onto captured pages. */
    private function groups(): array
    {
        return [
            'post'        => fn () => Page::where('is_published', true)->where('wp_type', 'post'),
            'page'        => fn () => Page::where('is_published', true)->where('wp_type', 'page'),
            'cases'       => fn () => Page::where('is_published', true)->where(function ($q) {
                $q->where('wp_type', 'cases')->orWhere('path', 'cases')->orWhere('path', 'like', '%/cases');
            }),
            'medical_faq' => fn () => Page::where('is_published', true)->where(function ($q) {
                $q->where('wp_type', 'medical_faq')->orWhere('path', 'like', '%medical-faq%');
            }),
            'category'    => fn () => Page::where('is_published', true)->where('path', 'like', '%category/%'),
            'faq_topic'   => fn () => Page::where('is_published', true)->where('path', 'like', '%faq-topic/%'),
            'author'      => fn () => Page::where('is_published', true)->where('path', 'like', 'author/%'),
        ];
    }

    public function index(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($this->groups() as $type => $query) {
            if (($query)()->exists()) {
                $xml .= '  <sitemap><loc>' . htmlspecialchars(url("/{$type}-sitemap.xml"), ENT_XML1) . '</loc></sitemap>' . "\n";
            }
        }
        $xml .= '</sitemapindex>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function type(string $type): Response
    {
        $groups = $this->groups();
        abort_unless(isset($groups[$type]), 404);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach (($groups[$type])()->get(['path', 'updated_at']) as $p) {
            $xml .= '  <url><loc>' . htmlspecialchars($this->loc($p->path), ENT_XML1) . '</loc>';
            if ($p->updated_at) {
                $xml .= '<lastmod>' . $p->updated_at->toAtomString() . '</lastmod>';
            }
            $xml .= '</url>' . "\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Build an absolute, properly-encoded, trailing-slash URL from a stored path. */
    private function loc(string $path): string
    {
        $base = rtrim(config('app.url'), '/');
        if ($path === '') {
            return $base . '/';
        }
        $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));

        return $base . '/' . $encoded . '/';
    }
}
