<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/customers', Railroad\Ecommerce\Controllers\CustomerJsonController::class . '@index')
        ->name('customers.index');

    Route::get('/customer/{id}', Railroad\Ecommerce\Controllers\CustomerJsonController::class . '@show')
        ->name('customer.show');
});
