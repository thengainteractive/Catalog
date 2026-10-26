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
        if (Schema::hasTable('catalog_products')) {
            Schema::table('catalog_products', function (Blueprint $table) {
                if (! Schema::hasColumn('catalog_products', 'has_variants')) {
                    $table->boolean('has_variants')->default(false)->after('sale_price');
                }
                if (! Schema::hasColumn('catalog_products', 'variant_attributes')) {
                    $table->json('variant_attributes')->nullable()->after('has_variants');
                }
            });
        }

        if (! Schema::hasTable('catalog_product_variants')) {
            Schema::create('catalog_product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('catalog_products')->cascadeOnDelete();
                $table->string('title');
                $table->string('sku')->nullable();
                $table->json('attributes')->nullable();
                $table->decimal('actual_price', 10, 2);
                $table->decimal('sale_price', 10, 2)->nullable();
                $table->string('image')->nullable();
                $table->json('gallery_images')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('position')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_product_variants');

        if (Schema::hasTable('catalog_products')) {
            Schema::table('catalog_products', function (Blueprint $table) {
                if (Schema::hasColumn('catalog_products', 'variant_attributes')) {
                    $table->dropColumn('variant_attributes');
                }
                if (Schema::hasColumn('catalog_products', 'has_variants')) {
                    $table->dropColumn('has_variants');
                }
            });
        }
    }
};
