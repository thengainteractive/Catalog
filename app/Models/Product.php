<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'description',
        'gallery_images',
    ];

    protected $casts = [
        'actual_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'gallery_images' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (Product $product) {
            if ($product->isDirty('gallery_images')) {
                $originalImages = (array) ($product->getOriginal('gallery_images') ?? []);
                $currentImages = (array) ($product->gallery_images ?? []);

                $removedImages = array_diff($originalImages, $currentImages);
                foreach ($removedImages as $path) {
                    if (! empty($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                    }
                }
            }
        });

        static::deleted(function (Product $product) {
            $images = (array) ($product->gallery_images ?? []);
            foreach ($images as $path) {
                if (! empty($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                }
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}

