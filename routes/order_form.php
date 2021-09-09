<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_public_groups'),
], function () {

    // order form json controller
    Route::get('/json/order-form', Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@index')
        ->name('json.order-form.index');

    Route::put('/json/order-form/submit', Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@submitOrder')
        ->name('json.order-form.submit');

    Route::put('/json/order-form/create-intent', Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@createIntent')
        ->name('json.order-form.create-intent');

    Route::put('/json/order-form/create-intent-payment', Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@createIntentPayment')
        ->name('json.order-form.create-intent-payment');

    // order form controller with redirect responses
    Route::post('/order-form/submit', Railroad\Ecommerce\Controllers\OrderFormController::class . '@submitOrder')
        ->name('order-form.submit');

    Route::get('/order-form/submit-paypal', Railroad\Ecommerce\Controllers\OrderFormController::class . '@submitPaypalOrder')
        ->name('order-form.submit-paypal');
});