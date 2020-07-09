<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_public_groups'),
], function () {

    Route::get('/add-to-cart', Railroad\Ecommerce\Controllers\CartController::class . '@addToCart')
        ->name('shopping-cart.add-to-cart');

    Route::get('/json/cart',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@index')
        ->name('shopping-cart.json.index');

    Route::delete('/json/clear-cart',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@clear')
        ->name('shopping-cart.json.clear-cart');

    Route::put('/json/add-to-cart',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@addCartItem')
        ->name('shopping-cart.json.remove-from-cart');

    Route::delete('/json/remove-from-cart/{productSku}',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@removeCartItem')
        ->name('shopping-cart.json.remove-from-cart');

    Route::patch('/json/update-product-quantity/{productSku}/{quantity}',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@updateCartItemQuantity')
        ->name('shopping-cart.json.update-cart-item-quantity');

    Route::put('/json/update-number-of-payments/{numberOfPayments}',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@updateNumberOfPayments')
        ->name('shopping-cart.json.update-number-of-payments');

    Route::put('/json/session-address',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@storeAddress')
        ->name('shopping-cart.json.session-address');

    Route::patch('/json/update-total-overrides',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@updateTotalOverrides')
        ->name('shopping-cart.json.update-total-overrides');

    Route::put('/session/address',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@storeAddress')
        ->name('shopping-cart.session-address');
});
