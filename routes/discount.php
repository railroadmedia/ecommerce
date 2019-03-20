<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/discounts', Railroad\Ecommerce\Controllers\DiscountJsonController::class . '@index')
        ->name('discounts.index');

    Route::get('/discount/{discountId}', Railroad\Ecommerce\Controllers\DiscountJsonController::class . '@show')
        ->name('discount.show');

    Route::put('/discount', [
            'uses' => Railroad\Ecommerce\Controllers\DiscountJsonController::class . '@store',
        ])
        ->name('discount.store');

    Route::patch('/discount/{discountId}', [
            'uses' => Railroad\Ecommerce\Controllers\DiscountJsonController::class . '@update',
        ])
        ->name('discount.update');

    Route::delete('/discount/{discountId}', [
            'uses' => Railroad\Ecommerce\Controllers\DiscountJsonController::class . '@delete',
        ])
        ->name('discount.delete');

});