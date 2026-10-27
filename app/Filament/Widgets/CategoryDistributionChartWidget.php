<?php

declare(strict_types=1);

namespace Modules\Catalog\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Models\Category;

class CategoryDistributionChartWidget extends ChartWidget
{
    protected ?string $heading = 'Products by Category';

    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

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

    protected function getData(): array
    {
        if (! Schema::hasTable('catalog_categories') || ! Schema::hasTable('catalog_products')) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $categories = Category::query()
            ->withCount('products')
            ->where('is_active', true)
            ->orderByDesc('products_count')
            ->limit(8)
            ->get();

        $labels = [];
        $data = [];
        $palette = [
            '#0d9488', // Teal
            '#f59e0b', // Amber
            '#6366f1', // Indigo
            '#10b981', // Emerald
            '#ec4899', // Pink
            '#8b5cf6', // Purple
            '#3b82f6', // Blue
            '#64748b', // Slate
        ];

        foreach ($categories as $category) {
            $labels[] = $category->name;
            $data[] = $category->products_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Products',
                    'data' => $data,
                    'backgroundColor' => array_slice($palette, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
