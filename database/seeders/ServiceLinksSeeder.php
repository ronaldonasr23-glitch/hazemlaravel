<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Setting;
use App\Support\ServiceLinker;
use Illuminate\Database\Seeder;

class ServiceLinksSeeder extends Seeder
{
    public function run(): void
    {
        // Footer (shared across the site)
        if ($footer = Setting::get('footer_html')) {
            Setting::put('footer_html', ServiceLinker::link($footer), 'layout');
        }

        // Page content (home page and any other page that lists the services)
        foreach (Page::all() as $page) {
            if ($page->content_html && str_contains($page->content_html, 'elementor-icon-list-text')) {
                $page->content_html = ServiceLinker::link($page->content_html);
                $page->saveQuietly();
            }
        }

        $this->command?->info('Service links applied to footer and pages.');
    }
}
