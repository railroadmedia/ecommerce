<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/stats/products', Railroad\Ecommerce\Controllers\StatsController::class . '@statsProduct')
        ->name('stats.products');

    Route::get('stats/orders', \Railroad\Ecommerce\Controllers\StatsController::class . '@statsOrder')
        ->name('stats.orders');

});