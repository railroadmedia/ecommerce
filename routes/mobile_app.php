<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.mobile_app'),
    'middleware' => config('ecommerce.route_middleware_mobile_app_receipt_validation_groups'),
], function () {

    Route::post('/apple/verify-receipt-and-process-payment', Railroad\Ecommerce\Controllers\AppleStoreKitController::class . '@processReceipt')
        ->name('apple_store_kit.process_receipt');

    Route::post(
        '/api/apple/signup',
        Railroad\Ecommerce\Controllers\AppleStoreKitController::class . '@signup'
    ) ->name('apple_store_kit.signup');

    Route::post('/api/apple/restore',
        Railroad\Ecommerce\Controllers\AppleStoreKitController::class . '@restorePurchase') ->name('apple_store_kit.restore');

    Route::post('/google/verify-receipt-and-process-payment', Railroad\Ecommerce\Controllers\GooglePlayStoreController::class . '@processReceipt')
        ->name('google_play_store.process_receipt');

    Route::post(
        '//api/google/signup',
        Railroad\Ecommerce\Controllers\GooglePlayStoreController::class . '@signup'
    ) ->name('google_play_store.signup');

    Route::post('/api/google/restore',
        Railroad\Ecommerce\Controllers\GooglePlayStoreController::class . '@restorePurchase') ->name('google_play_store.restore');
});

Route::group([
    'prefix' => config('ecommerce.mobile_app'),
    'middleware' => config('ecommerce.route_middleware_mobile_app_notifications_groups'),
], function () {
    Route::post('/apple/handle-server-notification', Railroad\Ecommerce\Controllers\AppleStoreKitController::class . '@processNotification')
        ->name('apple_store_kit.verify_receipt');

    Route::post('/google/handle-server-notification', Railroad\Ecommerce\Controllers\GooglePlayStoreController::class . '@processNotification')
        ->name('google_play_store.verify_receipt');
});
