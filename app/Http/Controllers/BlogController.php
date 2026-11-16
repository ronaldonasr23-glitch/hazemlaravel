<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Seo;

class BlogController extends Controller
{
    private const BODY = 'wp-theme-indutri tribe-no-js elementor-default elementor-kit-6';

    public function index()
    {
        $posts = Post::published()->latest('published_at')->paginate(9);

        $content = view('dynamic.blog-index', compact('posts'))->render();

        return view('site', [
            'head' => Seo::head([
                'title'       => 'News - ' . \App\Models\Setting::get('site_name'),
                'description' => 'Latest news and insights on chemical engineering, gas industries and plant operations.',
                'url'         => route('blog.index'),
            ]),
            'bodyClass'   => 'archive ' . self::BODY,
            'contentHtml' => $content,
        ]);
    }

    public function show(string $slug)
    {
        $post = Post::published()->where('slug', $slug)->first();

        if (! $post) {
            // Old duplicated permalinks carried a numeric suffix (e.g. "-2").
            // Redirect those to the canonical article when it exists.
            $base = preg_replace('/-\d+$/', '', $slug);
            if ($base !== $slug && ($canonical = Post::published()->where('slug', $base)->first())) {
                return redirect()->route('blog.show', $canonical->slug, 301);
            }
            abort(404);
        }

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->latest('published_at')->take(3)->get();

        $content = view('dynamic.blog-single', compact('post', 'related'))->render();

        return view('site', [
            'head' => Seo::head([
                'seo'            => $post->seo,
                'fallback_title' => $post->title . ' - ' . \App\Models\Setting::get('site_name'),
                'description'    => $post->excerpt,
                'image'          => $post->featured_image,
                'url'            => route('blog.show', $post->slug),
                'og_type'        => 'article',
                'published_at'   => optional($post->published_at)->toIso8601String(),
            ]),
            'bodyClass'   => 'single single-post ' . self::BODY,
            'contentHtml' => $content,
        ]);
    }
}
