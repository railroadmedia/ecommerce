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

    Route::get('/subscription/upgrade', SubscriptionUpgradeController::class . '@upgrade')
        ->name('subscription.upgrade');

    Route::get('/subscription/upgrade/rate',SubscriptionUpgradeController::class . '@upgradeRate')
        ->name('subscription.upgrade.rate');

    Route::get('/subscription/downgrade', SubscriptionUpgradeController::class . '@downgrade')
        ->name('subscription.upgrade');
});
