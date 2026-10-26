<?php

namespace Modules\Catalog\Services;

use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogService
{
    /**
     * Get all active categories with product counts.
     */
    public function getActiveCategoriesWithCounts(): Collection
    {
        return Category::where('is_active', true)
            ->withCount(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get paginated products with optional category and search filters.
     */
    public function getProducts(?int $categoryId = null, ?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::with([
            'category',
            'variants' => fn ($q) => $q->orderBy('position')->orderBy('id'),
        ])
            ->where('is_active', true)
            ->whereHas('category', function ($q) {
                $q->where('is_active', true);
            });

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($catQuery) use ($search) {
                      $catQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get total active products count.
     */
    public function getTotalProductsCount(): int
    {
        return Product::where('is_active', true)
            ->whereHas('category', function ($q) {
                $q->where('is_active', true);
            })->count();
    }

    /**
     * Find a product by ID or Slug.
     */
    public function findProduct(int|string $idOrSlug): ?Product
    {
        if (is_numeric($idOrSlug)) {
            return Product::with('category')->find($idOrSlug);
        }

        return Product::with('category')->where('slug', $idOrSlug)->first();
    }
}
