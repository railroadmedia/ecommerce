<?php

use Illuminate\Support\Facades\Route;
use Railroad\Ecommerce\Middleware\AdministratorMiddleware;

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

Route::get(
    '/product',
    Railroad\Ecommerce\Controllers\ProductJsonController::class . '@index'
)->name('product.index');

Route::put(
    '/product',
    [
        'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@store',
       // 'middleware' => ['permission'],
      //  'permissions' => ['admin'],
    ]
)->name('product.store');

Route::patch(
    '/product/{productId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@update',
        //'middleware' => ['permission'],
        //'permissions' => ['admin'],
    ]
)->name('product.update');

Route::delete(
    '/product/{productId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@delete',
        //'middleware' => ['permission'],
        //'permissions' => ['admin'],
    ]
)->name('product.delete');

Route::put(
    '/product/upload/',
    [
        'uses' => Railroad\Ecommerce\Controllers\ProductJsonController::class . '@uploadThumbnail',
        //'middleware' => ['permission'],
        //'permissions' => ['admin'],
    ]
)->name('product.upload');

Route::get(
    '/order',
    Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@index'
)->name('order.form');

Route::put(
    '/order',
    Railroad\Ecommerce\Controllers\OrderFormJsonController::class . '@submitOrder'
)->name('order.submit');

Route::put(
    '/shipping-option',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@store',
        //'middleware' => ['permission'],
        //'permissions' => ['admin'],
    ]
)->name('shipping-option.store');

Route::patch(
    '/shipping-option/{shippingOptionId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@update',
       // 'middleware' => ['permission'],
       // 'permissions' => ['admin'],
    ]
)->name('shipping-option.update');

Route::delete(
    '/shipping-option/{shippingOptionId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@delete',
      //  'middleware' => ['permission'],
       // 'permissions' => ['admin'],
    ]
)->name('shipping-option.delete');

Route::put(
    '/shipping-cost',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@store',
      //  'middleware' => ['permission'],
      //  'permissions' => ['admin'],
    ]
)->name('shipping-cost-weight-range.store');

Route::patch(
    '/shipping-cost/{shippingCostId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@update',
      //  'middleware' => ['permission'],
      //  'permissions' => ['admin'],
    ]

)->name('shipping-cost-weight-range.update');

Route::delete(
    '/shipping-cost/{shippingCostId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController::class . '@delete',
      //  'middleware' => ['permission'],
      //  'permissions' => ['admin'],
    ]
)->name('shipping-cost-weight-range.delete');

Route::put(
    '/payment',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@store',
       // 'middleware' => ['permission'],
       // 'permissions' => ['admin', 'isOwner'],
    ]
)->name('payment.store');

Route::put(
    '/refund',
    [
        'uses' => Railroad\Ecommerce\Controllers\RefundJsonController::class . '@store',
      //  'middleware' => ['permission'],
      //  'permissions' => ['admin', 'isOwner'],
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
     //   'middleware' => ['permission'],
     //   'permissions' => ['admin', 'isOwner'],
    ]
)->name('payment-method.update');

Route::delete(
    '/payment-method/{paymentMethodId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@delete',
      //  'middleware' => ['permission'],
      //  'permissions' => ['admin', 'isOwner'],
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
     //   'middleware' => ['permission'],
     //   'permissions' => ['admin', 'isOwner'],
    ]
)->name('address.update');

Route::delete(
    '/address/{addressId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\AddressJsonController::class . '@delete',
      //  'middleware' => ['permission'],
      //  'permissions' => ['admin', 'isOwner'],
    ]
)->name('address.delete');

Route::put(
    '/payment-gateway',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentGatewayJsonController::class . '@store',
     //   'middleware' => ['permission'],
    //    'permissions' => ['admin'],
    ]
)->name('paymentGateway.store');

Route::patch(
    '/payment-gateway/{paymentGatewayId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentGatewayJsonController::class . '@update',
      //  'middleware' => ['permission'],
     //   'permissions' => ['admin'],
    ]
)->name('paymentGateway.update');

Route::delete(
    '/payment-gateway/{paymentGatewayId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentGatewayJsonController::class . '@delete',
      //  'middleware' => ['permission'],
      //  'permissions' => ['admin'],
    ]
)->name('paymentGateway.delete');





