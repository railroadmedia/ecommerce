<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_public_groups'),
], function () {

    Route::put('/session/address', Railroad\Ecommerce\Controllers\SessionJsonController::class . '@storeAddress')
        ->name('session.store-address');

});