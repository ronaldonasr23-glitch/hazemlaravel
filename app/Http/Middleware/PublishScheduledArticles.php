<?php

namespace App\Http\Middleware;

use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * WordPress-style auto publishing: like WP-Cron, scheduled articles are
 * published on normal site visits once their time has passed — no server cron
 * required. Throttled to at most once a minute so it stays cheap, and wrapped
 * so a hiccup never breaks the page.
 */
class PublishScheduledArticles
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->is('admin*', 'livewire*')) {
            try {
                // Only one request a minute actually does the work.
                if (Cache::add('articles-publish-due-lock', 1, 55)) {
                    Post::query()
                        ->where('status', 'scheduled')
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now())
                        ->update(['status' => 'published']);
                }
            } catch (\Throwable $e) {
                // Never let scheduling interfere with serving the page.
            }
        }

        return $next($request);
    }
}
