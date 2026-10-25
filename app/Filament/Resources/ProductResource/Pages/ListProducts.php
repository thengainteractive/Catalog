<?php

namespace Modules\Catalog\Filament\Resources\ProductResource\Pages;

use Modules\Catalog\Filament\Exports\ProductExporter;
use Modules\Catalog\Filament\Imports\ProductImporter;
use Modules\Catalog\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\ImportAction::make()
                ->importer(ProductImporter::class)
                ->label('Import Products')
                ->icon('heroicon-o-arrow-up-tray'),
            Actions\ExportAction::make()
                ->exporter(ProductExporter::class)
                ->label('Export Products')
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }
}
