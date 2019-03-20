<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::post('stripe/webhook',
        \Railroad\Ecommerce\Controllers\StripeWebhookController::class . '@handleCustomerSourceUpdated');

});