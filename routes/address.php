<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/address', Railroad\Ecommerce\Controllers\AddressJsonController::class . '@index')
        ->name('address.index');

    Route::put('/address', Railroad\Ecommerce\Controllers\AddressJsonController::class . '@store')
        ->name('address.store');

    Route::patch('/address/{addressId}', [
            'uses' => Railroad\Ecommerce\Controllers\AddressJsonController::class . '@update',
        ])
        ->name('address.update');

    Route::delete('/address/{addressId}', [
            'uses' => Railroad\Ecommerce\Controllers\AddressJsonController::class . '@delete',
        ])
        ->name('address.delete');

});