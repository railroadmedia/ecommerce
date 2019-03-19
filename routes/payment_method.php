<?php

use Illuminate\Support\Facades\Route;

Route::put(
    '/payment-method',
    Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@store'
)->name('payment-method.store');

Route::get(
    '/payment-method/paypal-url',
    Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@getPaypalUrl'
)->name('payment-method.paypal.url');

Route::get(
    '/payment-method/paypal-agreement',
    Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@paypalAgreement'
)->name('payment-method.paypal.agreement');

Route::patch(
    '/payment-method/set-default',
    Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@setDefault'
)->name('payment-method.set-default');

Route::patch(
    '/payment-method/{paymentMethodId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@update',
    ]
)->name('payment-method.update');

Route::delete(
    '/payment-method/{paymentMethodId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@delete',
    ]
)->name('payment-method.delete');

Route::get(
    '/user-payment-method/{userId}',
    Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@getUserPaymentMethods'
)->name('user.payment-method.index');