<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_public_groups'),
], function () {

    // order form json controller
    Route::get('/json/order-form', Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@index')
        ->name('json.order-form.index');

    Route::put('/json/order-form/submit', Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@submitOrder')
        ->name('json.order-form.submit');
});