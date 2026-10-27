<?php

declare(strict_types=1);

namespace Modules\Catalog\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Filament\Resources\ProductResource;
use Modules\Catalog\Models\Product;

class LatestProductsWidget extends TableWidget
{
    protected static ?string $heading = 'Recently Added Products';

    protected static ?int $sort = 3;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

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

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('catalog_products')) {
            return $table->query(Product::query()->whereRaw('1 = 0'));
        }

        return $table
            ->query(
                Product::query()
                    ->with(['category', 'variants'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('')
                    ->circular()
                    ->disk('public')
                    ->state(function (Product $record): ?string {
                        $images = (array) ($record->gallery_images ?? []);
                        return $images[0] ?? null;
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->weight('medium')
                    ->limit(24)
                    ->description(fn (Product $record): ?string => $record->has_variants ? ($record->variants->count() . ' variants') : null),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->default('Uncategorized'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->state(function (Product $record): string {
                        if ($record->has_variants) {
                            $min = $record->min_price;
                            $max = $record->max_price;
                            if ($min < $max) {
                                return '₹' . number_format($min, 2) . ' – ' . number_format($max, 2);
                            }
                            return '₹' . number_format($min, 2);
                        }

                        $price = (float) ($record->sale_price ?? $record->actual_price ?? 0);
                        return '₹' . number_format($price, 2);
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
            ])
            ->paginated(false)
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}

