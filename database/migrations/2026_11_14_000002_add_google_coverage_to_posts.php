<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('google_coverage')->nullable()->after('google_index_status');
            $table->timestamp('google_checked_at')->nullable()->after('google_coverage');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['google_coverage', 'google_checked_at']);
        });
    }
};
