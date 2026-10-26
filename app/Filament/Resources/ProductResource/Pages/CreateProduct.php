<?php

namespace Modules\Catalog\Filament\Resources\ProductResource\Pages;

use Modules\Catalog\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['variant_attributes']) && is_array($data['variant_attributes'])) {
            $data['variant_attributes'] = array_values($data['variant_attributes']);
        }

        return $data;
    }
}
