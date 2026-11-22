<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\SiteShell;
use Illuminate\Http\Request;

/**
 * Renders the living blog — the article archive (/blogs) and single articles —
 * dynamically from the `posts` table, wrapped in the site's captured theme
 * chrome (see SiteShell) so it matches the rest of the site in both languages.
 *
 * Single articles are reached at their EXACT original permalink via the
 * catch-all PageController, which calls renderSingle() when a path resolves to
 * a published Post rather than a captured page — so no article URL 404s.
 */
class BlogController extends Controller
{
    /** The article archive: /blogs (English) and /ar/blogs (Arabic). */
    public function index(Request $request, string $language = 'en')
    {
        $language = $language === 'ar' ? 'ar' : 'en';
        $search = trim((string) $request->query('s', ''));

        $posts = Post::published()
            ->where('language', $language)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $inner = view('dynamic.blog-index', [
            'posts' => $posts,
            'rtl'   => $language === 'ar',
            'lang'  => $language,
        ])->render();

        return $this->wrap($language, $inner, [
            'title'       => ($search !== '' ? ($language === 'ar' ? 'بحث: ' : 'Search: ') . $search
                                             : ($language === 'ar' ? 'أحدث المقالات' : 'Latest Articles')),
            'description' => $language === 'ar'
                ? 'أحدث المقالات والنصائح الطبية من أ.د. حازم الفزاري في جراحة وتقويم العمود الفقري.'
                : 'Latest articles and medical insights from Prof. Dr. Hazem El Fezary on spine surgery and deformity correction.',
            'canonical'   => url($language === 'ar' ? '/ar/blogs/' : '/blogs/'),
            'og_type'     => 'website',
        ]);
    }

    /** Render a single article (called by PageController for article paths). */
    public function renderSingle(Post $post)
    {
        $language = $post->language === 'ar' ? 'ar' : 'en';

        $recent = Post::published()->where('language', $language)
            ->where('id', '!=', $post->id)
            ->latest('published_at')->take(5)->get();

        $related = Post::published()->where('language', $language)
            ->where('id', '!=', $post->id)
            ->latest('published_at')->take(3)->get();

        $inner = view('dynamic.blog-single', compact('post', 'related', 'recent'))->render();

        $seo = $post->seo;

        return $this->wrap($language, $inner, [
            'title'        => ($seo?->seo_title) ?: $post->title,
            'description'  => ($seo?->meta_description) ?: strip_tags($post->excerpt),
            'canonical'    => url($post->url()),
            'image'        => $post->featured_image,
            'og_type'      => 'article',
            'published_at' => optional($post->published_at)->toIso8601String(),
        ]);
    }

    /** Assemble inner content + per-page SEO into the captured site shell. */
    private function wrap(string $language, string $inner, array $seo)
    {
        $shell = SiteShell::get($language);

        return response()->view('dynamic.shell', [
            'head'      => SiteShell::head($language, $seo),
            'top'       => $shell['top'],
            'bottom'    => $shell['bottom'],
            'bodyClass' => $shell['bodyClass'],
            'lang'      => $shell['lang'],
            'dir'       => $shell['dir'],
            'content'   => $inner,
        ]);
    }
}
