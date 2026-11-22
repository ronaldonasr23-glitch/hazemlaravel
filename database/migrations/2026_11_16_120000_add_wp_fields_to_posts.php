<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Articles imported from the live WordPress source render dynamically at their
 * EXACT original permalinks (zero-404). To route them by path and keep the
 * Arabic/Polylang hierarchy + RTL, each Post carries its full relative path,
 * language and text direction, plus the source WP id for idempotent re-imports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('path', 500)->nullable()->unique()->after('slug');
            $table->string('language', 8)->default('en')->after('path');
            $table->string('html_dir', 8)->nullable()->after('language');
            $table->unsignedBigInteger('wp_id')->nullable()->index()->after('html_dir');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['path']);
            $table->dropColumn(['path', 'language', 'html_dir', 'wp_id']);
        });
    }
};
