<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.mobile_app'),
    'middleware' => config('ecommerce.mobile_app'),
], function () {

    Route::post('/apple/verify-receipt-and-process-payment', Railroad\Ecommerce\Controllers\AppleStoreKitController::class . '@processReceipt')
        ->name('apple_store_kit.process_receipt');

    Route::post('/google/verify-receipt-and-process-payment', Railroad\Ecommerce\Controllers\GooglePlayStoreController::class . '@processReceipt')
        ->name('google_play_store.process_receipt');
});

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_public_groups'),
], function () {
    Route::post('/apple/handle-server-notification', Railroad\Ecommerce\Controllers\AppleStoreKitController::class . '@processNotification')
        ->name('apple_store_kit.verify_receipt');

    Route::post('/google/handle-server-notification', Railroad\Ecommerce\Controllers\GooglePlayStoreController::class . '@processNotification')
        ->name('google_play_store.verify_receipt');
});