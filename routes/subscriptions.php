<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_logged_in_groups'),
], function () {

    Route::get('/subscriptions', Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@index')
        ->name('subscriptions.index');

    Route::put('/subscription', Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@store')
        ->name('subscription.store');

    Route::patch('/subscription/{subscriptionId}',
        Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@update')
        ->name('subscription.update');

    Route::delete('/subscription/{subscriptionId}', [
            'uses' => Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@delete',
        ])
        ->name('subscription.delete');

    Route::post('/subscription-renew/{subscriptionId}', [
            'uses' => Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@renew',
        ])
        ->name('subscription.renew');

});