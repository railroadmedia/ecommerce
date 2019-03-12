<?php

use Illuminate\Support\Facades\Route;

Route::get(
    '/address',
    Railroad\Ecommerce\Controllers\AddressJsonController::class . '@index'
)->name('address.index');

Route::put(
    '/address',
    Railroad\Ecommerce\Controllers\AddressJsonController::class . '@store'
)->name('address.store');

Route::patch(
    '/address/{addressId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\AddressJsonController::class . '@update',
    ]
)->name('address.update');

Route::delete(
    '/address/{addressId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\AddressJsonController::class . '@delete',
    ]
)->name('address.delete');
