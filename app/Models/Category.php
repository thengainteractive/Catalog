<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory;

    protected $table = 'catalog_categories';

    protected $fillable = [
        'name',
        'slug',
        'cover_image',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (Category $category) {
            if ($category->isDirty('cover_image')) {
                $originalCover = $category->getOriginal('cover_image');
                if (! empty($originalCover) && Storage::disk('public')->exists($originalCover)) {
                    Storage::disk('public')->delete($originalCover);
                }
            }
        });

        static::deleted(function (Category $category) {
            if (! empty($category->cover_image) && Storage::disk('public')->exists($category->cover_image)) {
                Storage::disk('public')->delete($category->cover_image);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
