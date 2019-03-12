<?php

use Illuminate\Support\Facades\Route;

Route::put(
    '/session/address',
    Railroad\Ecommerce\Controllers\SessionJsonController::class . '@storeAddress'
)->name('session.store-address');
