<?php

use Illuminate\Support\Facades\Route;

Route::get(
    '/payment',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@index',
    ]
)->name('payment.index');

Route::put(
    '/payment',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@store',
    ]
)->name('payment.store');

Route::delete(
    '/payment/{paymentId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@delete',
    ]
)->name('payment.delete');