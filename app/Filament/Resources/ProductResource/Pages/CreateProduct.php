<?php

namespace Modules\Catalog\Filament\Resources\ProductResource\Pages;

use Modules\Catalog\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
