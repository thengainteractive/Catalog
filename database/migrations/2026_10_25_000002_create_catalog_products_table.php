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
        Schema::create('catalog_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('catalog_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('actual_price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->longText('description')->nullable();
            $table->json('gallery_images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_products');
    }
};
