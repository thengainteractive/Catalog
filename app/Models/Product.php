<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $table = 'catalog_products';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'actual_price',
        'sale_price',
        'has_variants',
        'is_active',
        'variant_attributes',
        'description',
        'gallery_images',
    ];

    protected $casts = [
        'actual_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
        'variant_attributes' => 'array',
        'gallery_images' => 'array',
    ];

    protected static function booted(): void
    {
        // Self-healing schema for SQLite runtime environment
        if (Schema::hasTable('catalog_products')) {
            if (! Schema::hasColumn('catalog_products', 'has_variants')) {
                Schema::table('catalog_products', function (Blueprint $table) {
                    $table->boolean('has_variants')->default(false);
                });
            }
            if (! Schema::hasColumn('catalog_products', 'is_active')) {
                Schema::table('catalog_products', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true);
                });
            }
            if (! Schema::hasColumn('catalog_products', 'variant_attributes')) {
                Schema::table('catalog_products', function (Blueprint $table) {
                    $table->json('variant_attributes')->nullable();
                });
            }
        }

        if (Schema::hasTable('catalog_products') && ! Schema::hasTable('catalog_product_variants')) {
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

        static::updating(function (Product $product) {
            if ($product->isDirty('gallery_images')) {
                $originalImages = (array) ($product->getOriginal('gallery_images') ?? []);
                $currentImages = (array) ($product->gallery_images ?? []);

                $removedImages = array_diff($originalImages, $currentImages);
                foreach ($removedImages as $path) {
                    if (! empty($path) && Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
        });

        static::deleted(function (Product $product) {
            $images = (array) ($product->gallery_images ?? []);
            foreach ($images as $path) {
                if (! empty($path) && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->orderBy('position')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    public function getMinPriceAttribute(): float
    {
        if ($this->has_variants && $this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            $active = $this->variants->where('is_active', true);
            if ($active->isNotEmpty()) {
                return (float) $active->min(fn ($v) => $v->sale_price ?? $v->actual_price);
            }
        }
        return (float) ($this->sale_price ?? $this->actual_price ?? 0);
    }

    public function getMaxPriceAttribute(): float
    {
        if ($this->has_variants && $this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            $active = $this->variants->where('is_active', true);
            if ($active->isNotEmpty()) {
                return (float) $active->max(fn ($v) => $v->sale_price ?? $v->actual_price);
            }
        }
        return (float) ($this->sale_price ?? $this->actual_price ?? 0);
    }
}
