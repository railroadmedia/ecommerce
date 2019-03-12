<?php

use Illuminate\Support\Facades\Route;

Route::put(
    '/shipping-cost',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@store',
    ]
)->name('shipping-cost-weight-range.store');

Route::patch(
    '/shipping-cost/{shippingCostId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@update',
    ]

)->name('shipping-cost-weight-range.update');

Route::delete(
    '/shipping-cost/{shippingCostId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@delete',
    ]
)->name('shipping-cost-weight-range.delete');
