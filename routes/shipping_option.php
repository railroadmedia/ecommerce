<?php

use Illuminate\Support\Facades\Route;

Route::get(
    '/shipping-options',
    Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@index'
)->name('shipping-option.index');

Route::put(
    '/shipping-option',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@store',
    ]
)->name('shipping-option.store');

Route::patch(
    '/shipping-option/{shippingOptionId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@update',
    ]
)->name('shipping-option.update');

Route::delete(
    '/shipping-option/{shippingOptionId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@delete',
    ]
)->name('shipping-option.delete');
