<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::prefix('v1/catalog')->group(function () {
    Route::get('/categories', [\Modules\Catalog\Http\Controllers\Api\CatalogApiController::class, 'categories'])->name('api.catalog.categories');
    Route::get('/products', [\Modules\Catalog\Http\Controllers\Api\CatalogApiController::class, 'products'])->name('api.catalog.products');
    Route::get('/products/{idOrSlug}', [\Modules\Catalog\Http\Controllers\Api\CatalogApiController::class, 'show'])->name('api.catalog.products.show');
});
