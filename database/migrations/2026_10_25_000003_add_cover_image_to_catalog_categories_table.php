<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('catalog_categories') && ! Schema::hasColumn('catalog_categories', 'cover_image')) {
            Schema::table('catalog_categories', function (Blueprint $table) {
                $table->string('cover_image')->nullable()->after('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('catalog_categories') && Schema::hasColumn('catalog_categories', 'cover_image')) {
            Schema::table('catalog_categories', function (Blueprint $table) {
                $table->dropColumn('cover_image');
            });
        }
    }
};
