<?php

namespace Modules\Catalog\Filament\Exports;

use Modules\Catalog\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Storage;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Product Name'),
            ExportColumn::make('slug')
                ->label('Slug'),
            ExportColumn::make('category.name')
                ->label('Category'),
            ExportColumn::make('actual_price')
                ->label('Actual Price'),
            ExportColumn::make('sale_price')
                ->label('Sale Price'),
            ExportColumn::make('description')
                ->label('Description'),
            ExportColumn::make('gallery_images')
                ->label('Gallery Image URLs')
                ->formatStateUsing(function ($state): string {
                    if (blank($state)) {
                        return '';
                    }

                    $images = is_array($state) ? $state : json_decode($state, true);
                    if (blank($images) || !is_array($images)) {
                        return '';
                    }

                    // Export as comma-separated absolute public URLs so they can be viewed or re-imported
                    return collect($images)
                        ->map(fn (string $path) => Storage::disk('public')->url($path))
                        ->implode(', ');
                }),
            ExportColumn::make('created_at')
                ->label('Created At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product catalog export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
