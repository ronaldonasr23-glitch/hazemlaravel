<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Static/Elementor pages captured from the original site.
        // Keyed by the FULL relative path (hierarchical, per-language) so every
        // original WordPress URL resolves 1:1 and never 404s. '' = home page.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500);                 // full relative path, no leading/trailing slash ('' = home)
            $table->string('language', 8)->default('en'); // polylang language slug (en/ar)
            $table->string('slug')->nullable();           // WordPress post_name (not unique across hierarchy)
            $table->string('wp_type')->nullable();        // page|post|cases|medical_faq
            $table->unsignedBigInteger('wp_id')->nullable()->index();
            $table->unsignedBigInteger('wp_parent_id')->nullable();
            $table->string('title');
            $table->string('body_class', 1000)->nullable();
            $table->string('html_lang', 16)->nullable();  // <html lang="..."> value
            $table->string('html_dir', 8)->nullable();    // ltr|rtl
            $table->longText('head_html')->nullable();
            $table->longText('content_html')->nullable(); // full <body> inner html (header + content + footer)
            $table->unsignedInteger('menu_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique('path');
            $table->index(['wp_type', 'language']);
        });

        // Key/value site settings (identity, header/footer html, google, seo defaults)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->timestamps();
        });

        // Polymorphic SEO metadata (Yoast-style) for pages/posts/services/etc.
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('focus_keyphrase')->nullable();
            $table->text('keyphrase_synonyms')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('breadcrumb_title')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->string('schema_page_type')->default('WebPage');
            $table->string('schema_article_type')->default('Article');
            $table->boolean('is_cornerstone')->default(false);
            $table->boolean('noindex')->default(false);
            $table->boolean('nofollow')->default(false);
            $table->unsignedTinyInteger('seo_score')->default(0);      // 0-100 (linkdex)
            $table->unsignedTinyInteger('readability_score')->default(0);
            $table->unsignedInteger('reading_time')->default(0);
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_meta');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('pages');
    }
};
