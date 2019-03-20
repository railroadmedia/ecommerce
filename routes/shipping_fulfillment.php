<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/fulfillment', Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController::class . '@index')
        ->name('fulfillment.index');

    Route::patch('/fulfillment',
        Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController::class . '@markShippingFulfilled')
        ->name('fulfillment.fulfilled');

    Route::delete('/fulfillment', Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController::class . '@delete')
        ->name('fulfillment.fulfilled');

});