<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_public_groups'),
], function () {

    // order form json controller
    Route::get('/order', Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@index')
        ->name('order.form');

    Route::put('/order', Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@submitOrder')
        ->name('order.submit');

    // order form controller with redirect responses
    Route::post('/submit-order', Railroad\Ecommerce\Controllers\OrderFormController::class . '@submitOrder')
        ->name('order.submit.form');

    Route::get('/order-paypal', Railroad\Ecommerce\Controllers\OrderFormController::class . '@submitPaypalOrder')
        ->name('order.submit.paypal');

});