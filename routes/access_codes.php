<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::post(
        '/access-codes/redeem',
        Railroad\Ecommerce\Controllers\AccessCodeController::class . '@claim'
    )->name('access-codes.claim');

    Route::get(
        '/access-codes',
        Railroad\Ecommerce\Controllers\AccessCodeJsonController::class . '@index'
    )->name('access-codes.index');

    Route::get(
        '/access-codes/search',
        Railroad\Ecommerce\Controllers\AccessCodeJsonController::class . '@search'
    )->name('access-codes.search');

    Route::post(
        '/access-codes/claim',
        Railroad\Ecommerce\Controllers\AccessCodeJsonController::class . '@claim'
    )->name('access-codes.claim');

    Route::post(
        '/access-codes/release',
        Railroad\Ecommerce\Controllers\AccessCodeJsonController::class . '@release'
    )->name('access-codes.release');

});
