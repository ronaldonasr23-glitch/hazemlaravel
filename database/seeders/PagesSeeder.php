<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class PagesSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('seeders/raw');

        $read = fn (string $f) => is_file("$dir/$f") ? file_get_contents("$dir/$f") : null;

        // name => [slug, title, order]
        $pages = [
            'home'      => ['home', 'Home 1', 1],
            'about'     => ['about-page', 'About Us', 2],
            'company'   => ['company-overview', 'Company Overview', 3],
            'services'  => ['service-3-2-2', 'Services III', 4],
            'locations' => ['our-locations', 'Our Locations', 5],
            'team'      => ['volunteer', 'Meet Our Team', 6],
            'news'      => ['news', 'News', 7],
            'contact'   => ['contact-page', 'Contact Page', 8],
        ];

        foreach ($pages as $name => [$slug, $title, $order]) {
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title'        => $title,
                    'body_class'   => $this->extractBodyClass($read("$name.head.html"), $name),
                    'head_html'    => $read("$name.head.html"),
                    'content_html' => $read("$name.content.html"),
                    'menu_order'   => $order,
                    'is_published' => true,
                ]
            );
        }

        // Shared header/footer taken from a representative inner page (about),
        // with the active-menu classes stripped (JS re-applies them per route).
        $header = $read('about.header.html');
        $footer = $read('about.footer.html');
        $header = $this->stripActive($header);

        Setting::put('header_html', $header, 'layout');
        Setting::put('footer_html', $footer, 'layout');

        // Single templates for dynamic content types
        Setting::put('tpl_post_head', $read('blog.head.html'), 'templates');
        Setting::put('tpl_post_content', $read('blog.content.html'), 'templates');
        Setting::put('tpl_service_head', $read('service-single.head.html'), 'templates');
        Setting::put('tpl_service_content', $read('service-single.content.html'), 'templates');
        Setting::put('tpl_portfolio_head', $read('portfolio-single.head.html'), 'templates');
        Setting::put('tpl_portfolio_content', $read('portfolio-single.content.html'), 'templates');

        // Identity defaults
        Setting::put('site_name', 'Grow Gas integrated solution of chemical Engineering industry', 'identity');
    }

    private function stripActive(?string $html): ?string
    {
        if (! $html) {
            return $html;
        }

        return preg_replace(
            ['/\s*current-menu-item/', '/\s*current_page_item/', '/\s*page_item\b/', '/\s*page-item-\d+/'],
            '',
            $html
        );
    }

    private function extractBodyClass(?string $head, string $name): ?string
    {
        // body class lives on <body> in the full capture, not in <head>;
        // read it from the matching clean capture instead.
        $file = database_path("seeders/raw/$name.bodyclass.txt");
        if (is_file($file)) {
            return trim(file_get_contents($file));
        }

        return null;
    }
}
