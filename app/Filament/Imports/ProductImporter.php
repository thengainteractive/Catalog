<?php

namespace Modules\Catalog\Filament\Imports;

use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\ImageProcessingService;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Product Name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('slug')
                ->label('Slug')
                ->rules(['nullable', 'max:255'])
                ->fillRecordUsing(function (Product $record, ?string $state, array $data): void {
                    $slug = filled($state) ? Str::slug($state) : Str::slug($data['name'] ?? '');
                    $originalSlug = $slug;
                    $counter = 1;

                    // Ensure imported slug is unique in catalog_products table
                    while (Product::where('slug', $slug)->where('id', '!=', $record->id ?? 0)->exists()) {
                        $slug = "{$originalSlug}-{$counter}";
                        $counter++;
                    }

                    $record->slug = $slug;
                }),

            ImportColumn::make('category')
                ->label('Category Name')
                ->requiredMapping()
                ->relationship(name: 'category', resolveUsing: function (string $state): Category {
                    // Resolve category by name or create a new category automatically
                    $slug = Str::slug($state);
                    return Category::firstOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => trim($state),
                            'is_active' => true,
                        ]
                    );
                }),

            ImportColumn::make('actual_price')
                ->label('Actual Price')
                ->numeric()
                ->requiredMapping()
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('sale_price')
                ->label('Sale Price')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0']),

            ImportColumn::make('description')
                ->label('Description')
                ->rules(['nullable', 'string']),

            ImportColumn::make('gallery_images')
                ->label('Gallery Images (URLs or comma-separated list)')
                ->rules(['nullable'])
                ->fillRecordUsing(function (Product $record, mixed $state): void {
                    if (blank($state)) {
                        return;
                    }

                    $rawItems = is_array($state) ? $state : explode(',', (string) $state);
                    $processedImages = [];

                    foreach ($rawItems as $item) {
                        $trimmed = trim($item);
                        if (blank($trimmed)) {
                            continue;
                        }

                        // Convert any imported image to WebP 60% quality, max width 1024px
                        $savedPath = ImageProcessingService::processAndStoreFromUrl(
                            urlOrPath: $trimmed,
                            directory: 'catalog/products',
                            disk: 'public'
                        );

                        if ($savedPath) {
                            $processedImages[] = $savedPath;
                        }
                    }

                    if (!empty($processedImages)) {
                        $record->gallery_images = array_values(array_unique(array_merge($record->gallery_images ?? [], $processedImages)));
                    }
                }),
        ];
    }

    public function resolveRecord(): ?Product
    {
        // Update product if matching slug exists, otherwise instantiate a new Product
        if (filled($this->data['slug'] ?? null)) {
            $slug = Str::slug($this->data['slug']);
            $existing = Product::where('slug', $slug)->first();
            if ($existing) {
                return $existing;
            }
        }

        return new Product();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product catalog import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
