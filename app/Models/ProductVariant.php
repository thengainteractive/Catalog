<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'catalog_product_variants';

    protected $fillable = [
        'product_id',
        'title',
        'sku',
        'attributes',
        'actual_price',
        'sale_price',
        'image',
        'gallery_images',
        'is_active',
        'position',
    ];

    protected $casts = [
        'attributes' => 'array',
        'gallery_images' => 'array',
        'actual_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (ProductVariant $variant) {
            if ($variant->isDirty('image')) {
                $original = $variant->getOriginal('image');
                if (! empty($original) && Storage::disk('public')->exists($original)) {
                    Storage::disk('public')->delete($original);
                }
            }
        });

        static::deleted(function (ProductVariant $variant) {
            if (! empty($variant->image) && Storage::disk('public')->exists($variant->image)) {
                Storage::disk('public')->delete($variant->image);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the effective price (sale price if set, otherwise actual price).
     */
    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->actual_price ?? 0);
    }
}
