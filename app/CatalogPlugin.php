<?php

namespace Modules\Catalog;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CatalogPlugin implements Plugin
{
    public function getId(): string
    {
        return 'Catalog';
    }

    public function register(Panel $panel): void
    {
        // Auto-discover module Filament resources
        $panel->discoverResources(
            in: module_path('Catalog', 'app/Filament/Resources'),
            for: "Modules\\Catalog\\Filament\\Resources"
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
