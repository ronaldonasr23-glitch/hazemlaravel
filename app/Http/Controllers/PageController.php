<?php

namespace App\Http\Controllers;

use App\Models\Page;
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

        $page = Page::where('path', $path)->where('is_published', true)->first();

        abort_if($page === null, 404);

        return response()->view('captured', [
            'htmlLang'    => $page->html_lang ?: 'en-US',
            'htmlDir'     => $page->html_dir,
            'bodyClass'   => $page->body_class,
            'head'        => $page->head_html,
            'contentHtml' => $page->content_html,
        ]);
    }
}
