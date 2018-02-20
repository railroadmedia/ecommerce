<?php

use Illuminate\Support\Facades\Route;

Route::put(
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

Route::put(
    '/product',
    Railroad\Ecommerce\Controllers\ProductJsonController::class . '@store'
)->name('product.store');

Route::patch(
    '/product/{productId}',
    Railroad\Ecommerce\Controllers\ProductJsonController::class . '@update'
)->name('product.update');

Route::delete(
    '/product/{productId}',
    Railroad\Ecommerce\Controllers\ProductJsonController::class . '@delete'
)->name('product.delete');
