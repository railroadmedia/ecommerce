<?php

use Illuminate\Support\Facades\Route;
use Railroad\Ecommerce\Controllers\SubscriptionUpgradeController;

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

    Route::get('/failed-subscriptions', Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@failed')
        ->name('subscriptions.failed');

    Route::get('/failed-billing', Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@failedBilling')
        ->name('subscriptions.failed-billing');

    Route::get('/subscription/change/{tier}/{interval}', SubscriptionUpgradeController::class . '@change')
        ->whereIn('tier', ['plus', 'basic'])
        ->whereIn('interval', ['month', 'year'])
        ->name('subscription.change');

    Route::get('/subscription/change/info',SubscriptionUpgradeController::class . '@info')
        ->name('subscription.change.info');
});
