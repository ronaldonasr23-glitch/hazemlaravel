<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BlogController;
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

// Online appointment booking (the GET page is served at /online-appointment/ via
// the catch-all → PageController, which delegates to AppointmentController).
Route::post('/online-appointment/book', [AppointmentController::class, 'book'])->name('appointment.book');

// Living blog archive (dynamic, from the posts table) — English + Arabic.
// These win over the frozen captured /blogs page so new & scheduled articles show.
Route::get('/blogs', [BlogController::class, 'index'])->defaults('language', 'en')->name('blog.index');
Route::get('/blog', [BlogController::class, 'index'])->defaults('language', 'en');
Route::get('/ar/blogs', [BlogController::class, 'index'])->defaults('language', 'ar')->name('blog.index.ar');
Route::get('/ar/blog', [BlogController::class, 'index'])->defaults('language', 'ar');

// Catch-all: every original WordPress URL (hierarchical, both languages) resolves
// to its captured page by full path. MUST stay last. Reserved prefixes (admin
// panel, livewire, filament, storage assets) are excluded so their own routes win.
Route::get('/{path}', [PageController::class, 'show'])
    ->where('path', '^(?!admin$|admin/|livewire/|filament/|storage/).*$')
    ->name('page.show');
