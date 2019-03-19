<?php

use Illuminate\Support\Facades\Route;

Route::get(
    '/add-to-cart',
    Railroad\Ecommerce\Controllers\ShoppingCartController::class . '@addToCart'
)->name('shopping-cart.add-to-cart');

Route::put(
    '/remove-from-cart/{productId}',
    Railroad\Ecommerce\Controllers\ShoppingCartController::class . '@removeCartItem'
)->name('shopping-cart.remove-from-cart');

Route::put(
    '/update-product-quantity/{productId}/{quantity}',
    Railroad\Ecommerce\Controllers\ShoppingCartController::class . '@updateCartItemQuantity'
)->name('shopping-cart.update-cart-item-quantity');