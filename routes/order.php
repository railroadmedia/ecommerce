<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/orders', Railroad\Ecommerce\Controllers\OrderJsonController::class . '@index')
        ->name('orders.index');

    Route::get('/order/{orderId}', Railroad\Ecommerce\Controllers\OrderJsonController::class . '@show')
        ->name('order.read');

    Route::patch('/order/{orderId}', Railroad\Ecommerce\Controllers\OrderJsonController::class . '@update')
        ->name('order.update');

    Route::delete('/order/{orderId}', [
            'uses' => Railroad\Ecommerce\Controllers\OrderJsonController::class . '@delete',
        ])
        ->name('order.delete');

});