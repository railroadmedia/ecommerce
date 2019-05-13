<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/user-product', Railroad\Ecommerce\Controllers\UserProductJsonController::class . '@index')
        ->name('user-product.index');

    Route::put('/user-product', Railroad\Ecommerce\Controllers\UserProductJsonController::class . '@store')
        ->name('user-product.store');

    Route::patch('/user-product/{userProductId}', [
            'uses' => Railroad\Ecommerce\Controllers\UserProductJsonController::class . '@update',
        ])
        ->name('user-product.update');

    Route::delete('/user-product/{userProductId}', [
            'uses' => Railroad\Ecommerce\Controllers\UserProductJsonController::class . '@delete',
        ])
        ->name('user-product.delete');

});