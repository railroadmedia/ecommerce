<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('ecommerce.route_prefix'),
    'middleware' => config('ecommerce.route_middleware_public_groups'),
], function () {

    Route::get('/add-to-cart', Railroad\Ecommerce\Controllers\AddToCartController::class . '@addToCart')
        ->name('shopping-cart.add-to-cart');

    Route::put('/json/add-to-cart',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@addCartItem')
        ->name('shopping-cart.json.remove-from-cart');

    Route::delete('/json/remove-from-cart/{productSku}',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@removeCartItem')
        ->name('shopping-cart.json.remove-from-cart');

    Route::patch('/json/update-product-quantity/{productSku}/{quantity}',
        Railroad\Ecommerce\Controllers\CartJsonController::class . '@updateCartItemQuantity')
        ->name('shopping-cart.json.update-cart-item-quantity');

});