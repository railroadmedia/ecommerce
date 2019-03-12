<?php

use Illuminate\Support\Facades\Route;

Route::get(
    '/fulfillment',
    Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController::class . '@index'
)->name('fulfillment.index');

Route::patch(
    '/fulfillment',
    Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController::class . '@markShippingFulfilled'
)->name('fulfillment.fulfilled');

Route::delete(
    '/fulfillment',
    Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController::class . '@delete'
)->name('fulfillment.fulfilled');
