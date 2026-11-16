<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Home page (English front page = captured page with path '')
Route::get('/', [PageController::class, 'show'])->defaults('path', '')->name('home');

// XML sitemaps for search engines (index + per-type, mirroring the original Yoast layout)
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap_index.xml', [SitemapController::class, 'index']);
Route::get('/{type}-sitemap.xml', [SitemapController::class, 'type'])
    ->where('type', '[a-z_]+')
    ->name('sitemap.type');

// Contact form submission (kept for CF7-style forms if present)
Route::post('/contact-submit', [ContactController::class, 'submit'])->name('contact.submit');

// Catch-all: every original WordPress URL (hierarchical, both languages) resolves
// to its captured page by full path. MUST stay last. Reserved prefixes (admin
// panel, livewire, filament, storage assets) are excluded so their own routes win.
Route::get('/{path}', [PageController::class, 'show'])
    ->where('path', '^(?!admin$|admin/|livewire/|filament/|storage/).*$')
    ->name('page.show');
