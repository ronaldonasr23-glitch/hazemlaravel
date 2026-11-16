<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only GET/HEAD, never the admin panel, never the redirect endpoint itself.
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $path = '/' . trim(rawurldecode($request->path()), '/');
            $path = $path === '/' ? '/' : rtrim($path, '/');

            if ($path !== '/' && ! str_starts_with($path, '/admin')) {
                try {
                    $redirect = Redirect::where('from_path', $path)->where('is_active', true)->first();
                } catch (\Throwable $e) {
                    $redirect = null; // table not migrated yet
                }

                if ($redirect && $redirect->to_path !== $path) {
                    Redirect::whereKey($redirect->id)->increment('hits');

                    return redirect($redirect->to_path, $redirect->status_code);
                }
            }
        }

        return $next($request);
    }
}
