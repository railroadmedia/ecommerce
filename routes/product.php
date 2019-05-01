<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/products', Railroad\Ecommerce\Controllers\ProductJsonController::class . '@index')
        ->name('product.index');

    Route::get('/product/{productId}', Railroad\Ecommerce\Controllers\ProductJsonController::class . '@show')
        ->name('product.show');

    Route::put('/product', [
            'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@store',
        ])
        ->name('product.store');

    Route::patch('/product/{productId}', [
            'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@update',
        ])
        ->name('product.update');

    Route::delete('/product/{productId}', [
            'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@delete',
        ])
        ->name('product.delete');

    Route::put('/product/upload', [
            'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@uploadThumbnail',
        ])
        ->name('product.upload');

});