<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Http\Controllers\AppointmentController;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Resolve a captured page by its FULL relative path (hierarchical,
     * per-language). '' is the home page. Arabic paths arrive percent-encoded
     * and are decoded before lookup so they match the stored UTF-8 path.
     */
    public function show(Request $request, string $path = '')
    {
        $path = trim(rawurldecode($path), '/');

        // The online-appointment page is now a dynamic booking UI (served at its
        // exact original URL via the catch-all, so the trailing-slash URL in
        // Search Console still resolves 200).
        if ($path === 'online-appointment') {
            return app(AppointmentController::class)->index();
        }

        // The living-blog archive must always render dynamically from the posts
        // table — even at the trailing-slash URL /blogs/ (the canonical one),
        // which otherwise falls through to the frozen captured blog page and
        // never shows newly published / scheduled articles.
        if (in_array($path, ['blogs', 'blog'], true)) {
            return app(BlogController::class)->index($request, 'en');
        }
        if (in_array($path, ['ar/blogs', 'ar/blog'], true)) {
            return app(BlogController::class)->index($request, 'ar');
        }

        $page = Page::where('path', $path)->where('is_published', true)->first();

        // Articles are no longer captured as static pages — they render
        // dynamically from the posts table at their exact original permalink.
        // Scheduled (future) posts stay 404 until their date, exactly like WP.
        if ($page === null) {
            $post = Post::published()->where('path', $path)->first();
            abort_if($post === null, 404);

            return app(BlogController::class)->renderSingle($post);
        }

        return response()->view('captured', [
            'htmlLang'    => $page->html_lang ?: 'en-US',
            'htmlDir'     => $page->html_dir,
            'bodyClass'   => $page->body_class,
            'head'        => $page->head_html,
            'contentHtml' => $page->content_html,
        ]);
    }
}
