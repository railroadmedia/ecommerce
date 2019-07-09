<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.mobile_app'),
    'middleware' => config('ecommerce.mobile_app'),
], function () {

    Route::post('/apple/verify-receipt-and-process-payment', Railroad\Ecommerce\Controllers\AppleStoreKitController::class . '@processReceipt')
        ->name('apple_store_kit.verify_receipt');

});
