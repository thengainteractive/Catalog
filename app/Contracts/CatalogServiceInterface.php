<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CatalogServiceInterface
{
    /**
     * Check if the catalog module is enabled and its required database tables exist.
     */
    public function isAvailable(): bool;

    /**
     * Get all active categories with active product counts.
     */
    public function getActiveCategoriesWithCounts(): Collection;

    /**
     * Get paginated products with optional category and search filters.
     */
    public function getProducts(?int $categoryId = null, ?string $search = null, int $perPage = 12): LengthAwarePaginator;

    /**
     * Get total active products count.
     */
    public function getTotalProductsCount(): int;

    /**
     * Find a product by ID or Slug.
     */
    public function findProduct(int|string $idOrSlug): mixed;

    /**
     * Get transformed categories formatted specifically for storefront rendering.
     *
     * @return array<int, array{id: int, name: string, slug: string, count: int}>
     */
    public function getStorefrontCategories(): array;

    /**
     * Get transformed products formatted specifically for storefront rendering.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStorefrontProducts(): array;

    /**
     * Get combined storefront data payload (categories, products, and total count).
     *
     * @return array{categories: array<int, array<string, mixed>>, products: array<int, array<string, mixed>>, totalProductsCount: int}
     */
    public function getStorefrontPayload(): array;
}

