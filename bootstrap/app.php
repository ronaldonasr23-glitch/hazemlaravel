<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'contact-submit',
        ]);

        // 301 redirects for old URLs (WordPress canonicals + manual, managed
        // from the dashboard). Global so it also catches paths with no matching
        // route (would-be 404s) and guarantees indexed URLs never 404.
        $middleware->prepend(\App\Http\Middleware\HandleRedirects::class);

        // WordPress-style auto publishing: scheduled articles go live on normal
        // site visits once their time passes (throttled to once a minute), so no
        // server cron is required. The `articles:publish-due` command does the
        // same when a real scheduler IS running.
        $middleware->web(append: [
            \App\Http\Middleware\PublishScheduledArticles::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
