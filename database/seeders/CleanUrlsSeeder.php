<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CleanUrlsSeeder extends Seeder
{
    public function run(): void
    {
        // Replace absolute references to the original host (including JSON-escaped
        // slash forms used in inline Elementor config) with host/scheme-relative
        // paths so assets load from the current site over http or https.
        $replacements = [
            'https://chemgrowgas.test'   => '',
            'http://chemgrowgas.test'    => '',
            '//chemgrowgas.test'         => '',
            'https:\\/\\/chemgrowgas.test' => '',
            'http:\\/\\/chemgrowgas.test'  => '',
            '\\/\\/chemgrowgas.test'       => '',
            'https://chemgrowgas.com'    => '',
            'http://chemgrowgas.com'     => '',
            'https:\\/\\/chemgrowgas.com'  => '',
            'http:\\/\\/chemgrowgas.com'   => '',
            // Leftover theme-demo links -> local equivalents (longer first)
            'https://themegavias.com/wp/indutri/news/'   => '/news/',
            'https://themegavias.com/wp/indutri/news'    => '/news',
            'https://themegavias.com/wp/indutri/'        => '/',
            'https://themegavias.com/wp/indutri'         => '/',
            'https:\\/\\/themegavias.com\\/wp\\/indutri\\/news\\/' => '\\/news\\/',
            'https:\\/\\/themegavias.com\\/wp\\/indutri\\/'        => '\\/',
            // Force external fonts/CDNs to HTTPS to avoid mixed content
            'http://fonts.googleapis.com' => 'https://fonts.googleapis.com',
            'http://fonts.gstatic.com'    => 'https://fonts.gstatic.com',
            'http:\\/\\/fonts.googleapis.com' => 'https:\\/\\/fonts.googleapis.com',
            'http:\\/\\/fonts.gstatic.com'    => 'https:\\/\\/fonts.gstatic.com',
        ];

        // Remove the leftover Yoast "noindex" robots meta that the original
        // WordPress site emitted while search engines were discouraged (staging).
        // Keep the healthy "index, follow" robots meta untouched.
        $stripNoindex = fn (?string $s) => $s === null ? null : preg_replace(
            '/<meta[^>]*name=["\']robots["\'][^>]*noindex[^>]*>\s*/i',
            '',
            $s
        );

        $apply = fn (?string $s) => $s === null ? null : $stripNoindex(
            str_replace(array_keys($replacements), array_values($replacements), $s)
        );

        $pages = 0;
        foreach (Page::all() as $page) {
            $page->head_html = $apply($page->head_html);
            $page->content_html = $apply($page->content_html);
            $page->saveQuietly();
            $pages++;
        }

        $settings = 0;
        foreach (Setting::all() as $setting) {
            $new = $apply($setting->value);
            if ($new !== $setting->value) {
                $setting->value = $new;
                $setting->saveQuietly();
                $settings++;
            }
        }

        \Illuminate\Support\Facades\Cache::flush();

        $this->command->info("Cleaned URLs in {$pages} pages and {$settings} settings.");
    }
}
