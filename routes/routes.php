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

Route::get(
    '/order',
    Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@index'
)->name('order.form');

Route::put(
    '/shipping-option',
    Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@store'
)->name('shipping-option.store');

Route::patch(
    '/shipping-option/{shippingOptionId}',
    Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@update'
)->name('shipping-option.update');

Route::delete(
    '/shipping-option/{shippingOptionId}',
    Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@delete'
)->name('shipping-option.delete');

Route::put(
    '/shipping-cost',
    Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@store'
)->name('shipping-cost-weight-range.store');

Route::patch(
    '/shipping-cost/{shippingCostId}',
    Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@update'
)->name('shipping-cost-weight-range.update');

Route::delete(
    '/shipping-cost/{shippingCostId}',
    Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@delete'
)->name('shipping-cost-weight-range.delete');


