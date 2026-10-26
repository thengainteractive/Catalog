<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Services\CatalogService;

class CatalogApiController extends Controller
{
    public function __construct(
        protected CatalogService $catalogService
    ) {}

    /**
     * Get active categories with products count.
     */
    public function categories(): JsonResponse
    {
        $categories = $this->catalogService->getActiveCategoriesWithCounts();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * Get paginated products with optional category and search filters.
     */
    public function products(Request $request): JsonResponse
    {
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 12);

        $products = $this->catalogService->getProducts(
            categoryId: $categoryId,
            search: $search,
            perPage: $perPage
        );

        return response()->json([
            'status' => 'success',
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get single product details.
     */
    public function show(string $idOrSlug): JsonResponse
    {
        $product = $this->catalogService->findProduct($idOrSlug);

        if (! $product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $product,
        ]);
    }
}
