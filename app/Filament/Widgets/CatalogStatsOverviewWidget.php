<?php

declare(strict_types=1);

namespace Modules\Catalog\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

class CatalogStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        try {
            return $user->can('view_catalog');
        } catch (\Throwable) {
            return false;
        }
    }

    protected function getStats(): array
    {
        if (! Schema::hasTable('catalog_products')) {
            return [];
        }

        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $inactiveProducts = $totalProducts - $activeProducts;

        $activeCategories = Schema::hasTable('catalog_categories')
            ? Category::where('is_active', true)->count()
            : 0;

        $totalVariants = Schema::hasTable('catalog_product_variants')
            ? ProductVariant::count()
            : 0;

        $saleProducts = Product::whereNotNull('sale_price')
            ->where('is_active', true)
            ->whereRaw('sale_price < actual_price')
            ->count();

        return [
            Stat::make('Total Products', (string) $totalProducts)
                ->description("{$activeProducts} active, {$inactiveProducts} inactive")
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Categories', (string) $activeCategories)
                ->description('Active catalog categories')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info'),

            Stat::make('Product Variations', (string) $totalVariants)
                ->description('Configured variations & SKUs')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('gray'),

            Stat::make('Promotional Items', (string) $saleProducts)
                ->description('Active items with sale pricing')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color($saleProducts > 0 ? 'success' : 'gray'),
        ];
    }
}

