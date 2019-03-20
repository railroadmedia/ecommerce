<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::put('/shipping-cost', [
            'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@store',
        ])
        ->name('shipping-cost-weight-range.store');

    Route::patch('/shipping-cost/{shippingCostId}', [
            'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@update',
        ]

    )
        ->name('shipping-cost-weight-range.update');

    Route::delete('/shipping-cost/{shippingCostId}', [
            'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@delete',
        ])
        ->name('shipping-cost-weight-range.delete');

});