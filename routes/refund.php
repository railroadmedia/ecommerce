<?php

use Illuminate\Support\Facades\Route;

Route::put(
    '/refund',
    [
        'uses' => Railroad\Ecommerce\Controllers\RefundJsonController::class . '@store',
    ]
)->name('refund.store');
