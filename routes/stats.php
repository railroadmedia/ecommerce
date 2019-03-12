<?php

use Illuminate\Support\Facades\Route;

Route::get(
    '/stats/products',
    Railroad\Ecommerce\Controllers\StatsController::class . '@statsProduct'
)->name('stats.products');

Route::get(
    'stats/orders',
    \Railroad\Ecommerce\Controllers\StatsController::class . '@statsOrder'
)->name('stats.orders');
