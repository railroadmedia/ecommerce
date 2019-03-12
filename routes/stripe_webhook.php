<?php

use Illuminate\Support\Facades\Route;

Route::post(
    'stripe/webhook',
    \Railroad\Ecommerce\Controllers\StripeWebhookController::class . '@handleCustomerSourceUpdated'
);
