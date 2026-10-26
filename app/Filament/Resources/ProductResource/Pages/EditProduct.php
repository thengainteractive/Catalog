<?php

namespace Modules\Catalog\Filament\Resources\ProductResource\Pages;

use Modules\Catalog\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['variant_attributes']) && ! is_array($data['variant_attributes'])) {
            $decoded = json_decode((string) $data['variant_attributes'], true);
            $data['variant_attributes'] = is_array($decoded) ? $decoded : [];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['variant_attributes']) && is_array($data['variant_attributes'])) {
            $data['variant_attributes'] = array_values($data['variant_attributes']);
        }

        return $data;
    }
}
