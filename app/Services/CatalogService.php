<?php

declare(strict_types=1);

namespace Modules\Catalog\Services;

use App\Core\ModuleRegistry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\Contracts\CatalogServiceInterface;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class CatalogService implements CatalogServiceInterface
{
    /**
     * In-memory cache for availability status within the request lifecycle.
     */
    protected static ?bool $cachedAvailability = null;

    /**
     * Check if the catalog module is enabled and its required database tables exist.
     */
    public function isAvailable(): bool
    {
        if (static::$cachedAvailability !== null) {
            return static::$cachedAvailability;
        }

        if (! ModuleRegistry::isEnabled('Catalog')) {
            return static::$cachedAvailability = false;
        }

        try {
            return static::$cachedAvailability = (
                Schema::hasTable('catalog_categories') &&
                Schema::hasTable('catalog_products')
            );
        } catch (\Throwable) {
            return static::$cachedAvailability = false;
        }
    }

    /**
     * Reset the cached availability status (useful during testing or state toggling).
     */
    public static function resetAvailabilityCache(): void
    {
        static::$cachedAvailability = null;
    }

    /**
     * Helper to generate clean relative URLs ('storage/...') for static site portability.
     */
    protected function toRelativeStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        $url = Storage::url($path);
        $parsed = parse_url($url, PHP_URL_PATH) ?: $url;
        return ltrim($parsed, '/');
    }

    /**
     * Get all active categories with product counts.
     */
    public function getActiveCategoriesWithCounts(): Collection
    {
        if (! $this->isAvailable()) {
            return new Collection();
        }

        return Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($q) => $q->where('is_active', true))
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get paginated products with optional category and search filters.
     */
    public function getProducts(?int $categoryId = null, ?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        if (! $this->isAvailable()) {
            return new ConcreteLengthAwarePaginator([], 0, $perPage);
        }

        $query = Product::query()
            ->with([
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
        if (! $this->isAvailable()) {
            return 0;
        }

        return Product::query()
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->count();
    }

    /**
     * Find a product by ID or Slug.
     */
    public function findProduct(int|string $idOrSlug): ?Product
    {
        if (! $this->isAvailable()) {
            return null;
        }

        if (is_numeric($idOrSlug)) {
            return Product::with('category')->find($idOrSlug);
        }

        return Product::with('category')->where('slug', $idOrSlug)->first();
    }

    /**
     * Get transformed categories formatted specifically for storefront rendering.
     *
     * @return array<int, array{id: int, name: string, slug: string, count: int}>
     */
    public function getStorefrontCategories(): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $categories = $this->getActiveCategoriesWithCounts();

        return $categories->map(function (Category $c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug ?: Str::slug($c->name),
                'count' => (int) ($c->products_count ?? 0),
            ];
        })->values()->all();
    }

    /**
     * Get transformed products formatted specifically for storefront rendering.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStorefrontProducts(): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $products = Product::query()
            ->with([
                'category',
                'variants' => fn ($q) => $q->orderBy('position')->orderBy('id'),
            ])
            ->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->latest()
            ->get();

        return $products->map(function (Product $p) {
            $images = (array) ($p->gallery_images ?? []);
            $galleryUrls = [];
            foreach ($images as $img) {
                if (! empty($img)) {
                    $url = $this->toRelativeStorageUrl($img);
                    if ($url) {
                        $galleryUrls[] = $url;
                    }
                }
            }
            $imageUrl = $galleryUrls[0] ?? null;

            $transformedVariants = [];
            if ($p->has_variants && $p->variants->isNotEmpty()) {
                $transformedVariants = $p->variants->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'title' => $v->title,
                        'sku' => $v->sku,
                        'attributes' => (array) ($v->attributes ?? []),
                        'price' => (float) ($v->sale_price ?? $v->actual_price ?? 0),
                        'original_price' => $v->sale_price ? (float) $v->actual_price : null,
                        'image' => $this->toRelativeStorageUrl($v->image),
                        'is_active' => (bool) $v->is_active,
                    ];
                })->values()->all();
            }

            $activeVariants = collect($transformedVariants)->where('is_active', true);
            $hasActiveVariants = $activeVariants->isNotEmpty();
            if ($hasActiveVariants) {
                $minPrice = (float) $activeVariants->min('price');
                $maxPrice = (float) $activeVariants->max('price');
                $price = $minPrice;
                $originalPrice = null;
                $priceRange = ($minPrice < $maxPrice)
                    ? ('₹' . number_format($minPrice, 2) . ' – ₹' . number_format($maxPrice, 2))
                    : ('₹' . number_format($minPrice, 2));
            } elseif (! empty($transformedVariants)) {
                $minPrice = (float) collect($transformedVariants)->min('price');
                $maxPrice = (float) collect($transformedVariants)->max('price');
                $price = $minPrice;
                $originalPrice = null;
                $priceRange = ($minPrice < $maxPrice)
                    ? ('₹' . number_format($minPrice, 2) . ' – ₹' . number_format($maxPrice, 2))
                    : ('₹' . number_format($minPrice, 2));
            } else {
                $price = (float) ($p->sale_price ?? $p->actual_price ?? 0);
                $originalPrice = (float) ($p->actual_price ?? 0);
                $minPrice = $price;
                $maxPrice = $price;
                $priceRange = '₹' . number_format($price, 2);
            }

            $normalizedAttributes = array_values(array_filter(array_map(function ($attr) {
                $name = trim((string) ($attr['name'] ?? ''));
                if ($name === '') return null;
                $rawVals = $attr['values'] ?? [];
                if (is_string($rawVals)) {
                    $rawVals = explode(',', $rawVals);
                }
                $cleanVals = array_values(array_filter(array_map('trim', (array) $rawVals)));
                if (empty($cleanVals)) return null;
                return [
                    'name' => $name,
                    'values' => $cleanVals,
                ];
            }, (array) ($p->variant_attributes ?? []))));

            return [
                'id' => $p->id,
                'name' => $p->name,
                'category_id' => $p->category_id,
                'category_name' => $p->category ? $p->category->name : 'Uncategorized',
                'price' => $price,
                'original_price' => ($originalPrice && $originalPrice > $price) ? $originalPrice : null,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'price_range' => $priceRange,
                'has_variants' => $hasActiveVariants,
                'variant_attributes' => $normalizedAttributes,
                'variants' => $transformedVariants,
                'slug' => $p->slug ?: Str::slug($p->name),
                'sku' => $p->slug,
                'description' => $p->description ? strip_tags($p->description) : '',
                'image' => $imageUrl,
                'gallery' => $galleryUrls,
            ];
        })->values()->all();
    }

    /**
     * Get combined storefront data payload (categories, products, and total count).
     *
     * @return array{categories: array<int, array<string, mixed>>, products: array<int, array<string, mixed>>, totalProductsCount: int}
     */
    public function getStorefrontPayload(): array
    {
        $categories = $this->getStorefrontCategories();
        $products = $this->getStorefrontProducts();

        return [
            'categories' => $categories,
            'products' => $products,
            'totalProductsCount' => count($products),
        ];
    }
}
