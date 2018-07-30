<?php

use Illuminate\Support\Facades\Route;
use Railroad\Ecommerce\Middleware\AdministratorMiddleware;

//shopping cart
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

//product
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

//order form
Route::get(
    '/order',
    Railroad\Ecommerce\Controllers\OrderFormController::class . '@index'
)->name('order.form');

Route::put(
    '/order',
    Railroad\Ecommerce\Controllers\OrderFormController::class . '@submitOrder'
)->name('order.submit');

//shipping option
Route::get(
    '/shipping-options',
    Railroad\Ecommerce\Controllers\ShippingOptionController::class . '@index'
)->name('shipping-option.index');
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

//shipping costs weight range
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

//payment
Route::get(
    '/payment',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@index',
    ]
)->name('payment.index');

Route::put(
    '/payment',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@store',
    ]
)->name('payment.store');

Route::delete(
    '/payment/{paymentId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\PaymentJsonController::class . '@delete',
    ]
)->name('payment.delete');

//refund
Route::put(
    '/refund',
    [
        'uses' => Railroad\Ecommerce\Controllers\RefundJsonController::class . '@store',
    ]
)->name('refund.store');

//payment method
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
Route::get(
    '/user-payment-method/{userId}',
    Railroad\Ecommerce\Controllers\PaymentMethodJsonController::class . '@getUserPaymentMethods'
)->name('user.payment-method.index');

//address
Route::get(
    '/address',
    Railroad\Ecommerce\Controllers\AddressJsonController::class . '@index'
)->name('address.index');

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

//discount
Route::get(
    '/discounts',
    Railroad\Ecommerce\Controllers\DiscountJsonController::class . '@index'
)->name('discounts.index');
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

//discount criteria
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

//subscriptions
Route::get(
    '/subscriptions',
    Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@index'
)->name('subscriptions.index');
Route::put(
    '/subscription',
    Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@store'
)->name('subscription.store');
Route::patch(
    '/subscription/{subscriptionId}',
    Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@update'
)->name('subscription.update');
Route::delete(
    '/subscription/{subscriptionId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@delete',
    ]
)->name('subscription.delete');

Route::post(
    '/subscription-renew/{subscriptionId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\SubscriptionJsonController::class . '@renew',
    ]
)->name('subscription.renew');

//order
Route::get(
    '/orders',
    Railroad\Ecommerce\Controllers\OrderJsonController::class . '@index'
)->name('orders.index');
Route::patch(
    '/order/{orderId}',
    Railroad\Ecommerce\Controllers\OrderJsonController::class . '@update'
)->name('order.update');
Route::delete(
    '/order/{orderId}',
    [
        'uses' => Railroad\Ecommerce\Controllers\OrderJsonController::class . '@delete',
    ]
)->name('order.delete');

//shipping fulfillment
Route::get(
    '/fulfillment',
    Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController::class . '@index'
)->name('fulfillment.index');
Route::patch(
    '/fulfillment',
    Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController::class . '@markShippingFulfilled'
)->name('fulfillment.fulfilled');
Route::delete(
    '/fulfillment',
    Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController::class . '@delete'
)->name('fulfillment.fulfilled');

//stripe webhook
Route::post(
    'stripe/webhook',
    \Railroad\Ecommerce\Controllers\StripeWebhookController::class . '@handleCustomerSourceUpdated'
);






