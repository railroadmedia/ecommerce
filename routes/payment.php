<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {
    
    Route::get('/payment', [
            'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@index',
        ])
        ->name('payment.index');

    Route::put('/payment', [
            'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@store',
        ])
        ->name('payment.store');

    Route::delete('/payment/{paymentId}', [
            'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@delete',
        ])
        ->name('payment.delete');

});