<?php

use Illuminate\Support\Facades\Route;
use Railroad\Ecommerce\Middleware\AdministratorMiddleware;

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

Route::get(
    '/product',
    Railroad\Ecommerce\Controllers\ProductJsonController::class . '@index'
)->name('product.index');

Route::put(
    '/product',
    [
        'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@store',
    ]
)->name('product.store');

Route::patch(
    '/product/{productId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@update',
    ]
)->name('product.update');

Route::delete(
    '/product/{productId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@delete',
    ]
)->name('product.delete');

Route::put(
    '/product/upload/',
    [
        'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@uploadThumbnail',
    ]
)->name('product.upload');

Route::get(
    '/order',
    Railroad\Ecommerce\Controllers\OrderFormController::class . '@index'
)->name('order.form');

Route::put(
    '/order',
    Railroad\Ecommerce\Controllers\OrderFormController::class . '@submitOrder'
)->name('order.submit');

Route::put(
    '/shipping-option',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@store',
    ]
)->name('shipping-option.store');

Route::patch(
    '/shipping-option/{shippingOptionId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@update',
    ]
)->name('shipping-option.update');

Route::delete(
    '/shipping-option/{shippingOptionId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@delete',
    ]
)->name('shipping-option.delete');

Route::put(
    '/shipping-cost',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@store',
    ]
)->name('shipping-cost-weight-range.store');

Route::patch(
    '/shipping-cost/{shippingCostId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@update',
    ]

)->name('shipping-cost-weight-range.update');

Route::delete(
    '/shipping-cost/{shippingCostId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@delete',
    ]
)->name('shipping-cost-weight-range.delete');

Route::put(
    '/payment',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@store',
    ]
)->name('payment.store');

Route::put(
    '/refund',
    [
        'uses' => Railroad\Ecommerce\Controllers\RefundJsonController::class . '@store',
    ]
)->name('refund.store');

Route::put(
    '/payment-method',
    Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@store'
)->name('payment-method.store');

Route::patch(
    '/payment-method/{paymentMethodId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@update',
    ]
)->name('payment-method.update');

Route::delete(
    '/payment-method/{paymentMethodId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@delete',
    ]
)->name('payment-method.delete');

Route::put(
    '/address',
    Railroad\Ecommerce\Controllers\AddressJsonController::class . '@store'
)->name('address.store');

Route::patch(
    '/address/{addressId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\AddressJsonController::class . '@update',
    ]
)->name('address.update');

Route::delete(
    '/address/{addressId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\AddressJsonController::class . '@delete',
    ]
)->name('address.delete');

Route::put(
    '/discount',
    [
        'uses' => Railroad\Ecommerce\Controllers\DiscountJsonController::class . '@store',
    ]
)->name('discount.store');

Route::patch(
    '/discount/{discountId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\DiscountJsonController::class . '@update',
    ]
)->name('discount.update');

Route::delete(
    '/discount/{discountId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\DiscountJsonController::class . '@delete',
    ]
)->name('discount.delete');

Route::put(
    '/discount-criteria/{discountId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\DiscountCriteriaJsonController::class . '@store',
    ]
)->name('discount.criteria.store');

Route::patch(
    '/discount-criteria/{discountCriteriaId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\DiscountCriteriaJsonController::class . '@update',
    ]
)->name('discount.criteria.update');

Route::delete(
    '/discount-criteria/{discountCriteriaId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\DiscountCriteriaJsonController::class . '@delete',
    ]
)->name('discount.criteria.delete');





