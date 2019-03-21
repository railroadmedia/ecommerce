<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderFormControllerTest extends EcommerceTestCase
{
    /**
     * @var CartService
     */
    protected $cartService;

    protected function setUp()
    {
        parent::setUp();
        $this->cartService = $this->app->make(CartService::class);
    }

    public function test_submit_order()
    {
        $userId = $this->createAndLogInNewUser();

        // $currency = $this->defaultCurrency;
        $currency = $this->getCurrency();

        $orderData = [
            'payment_method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'billing-region' => $this->faker->word,
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
            'company_name' => $this->faker->creditCardType,
            'gateway' => ConfigService::$brand,
            'shipping-first-name' => $this->faker->firstName,
            'shipping-last-name' => $this->faker->lastName,
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => $this->faker->city,
            'shipping-region' => 'ab',
            'shipping-zip-or-postal-code' => $this->faker->postcode,
            'shipping-country' => 'Canada',
            'currency' => $currency
        ];

        $session = $this->app->make(Store::class);

        $session->flush();

        $this->session(['order-form-input' => $orderData]);

        $cartAddressService = $this->app->make(CartAddressService::class);

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($orderData['billing-country'])
            ->setState($orderData['billing-region'])
            ->setZipOrPostalCode($orderData['billing-zip-or-postal-code']);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 0.20,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $discount = $this->fakeDiscount([
            'active' => true,
            'amount' => 10,
            'type' => 'product amount off',
            'product_id' => $productOne['id'],
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id'],
            'product_id' => $productOne['id'],
            'type' => 'product quantity requirement',
            'min' => '1',
            'max' => '2000000',
        ]);

        $productOneQuantity = 1;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productOne['id'],
            ]
        );

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductOneDiscountAmount = round($discount['amount'] * $productOneQuantity, 2);

        $expectedProductOneDiscountedPrice = round($expectedProductOneTotalPrice - $expectedProductOneDiscountAmount, 2);

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $expectedProductTwoTotalPrice = round($productTwo['price'] * $productTwoQuantity, 2);

        $expectedProductTwoDiscountedPrice = 0;


        $expectedProductDue = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice;

        $expectedTotalFromItems = round($expectedProductOneDiscountedPrice + $expectedProductTwoTotalPrice, 2);

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = $expectedTotalFromItems * $taxRate + $shippingCostAmount * $taxRate;

        $expectedOrderTotalDue = $expectedTotalFromItems + $shippingCostAmount + $expectedTaxes;

        $billingAgreementId = rand(1,100);

        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn($billingAgreementId);

        $transactionId = rand(1,100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        ConfigService::$paypalAgreementFulfilledRoute = 'order.submit.paypal';

        $paypalToken = $this->faker->word;

        $response = $this->call(
            'GET',
            '/order-paypal',
            ['token' => $paypalToken]
        );

        // assert response code
        $this->assertEquals(302, $response->getStatusCode());

        // assert session results
        $response->assertSessionHas('success', true);
        $response->assertSessionHas('order');

        // assert database - all persisted entities

        // assert database records
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // billingAddress
        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'zip' => $orderData['billing-zip-or-postal-code'],
                'state' => $orderData['billing-region'],
                'country' => $orderData['billing-country'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // billingAgreement
        $this->assertDatabaseHas(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'external_id' => $billingAgreementId,
                'payment_gateway_name' => ConfigService::$brand,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // paymentMethod
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // userPaymentMethods
        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'is_primary' => true,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService
            ->convertFromBase(round($expectedOrderTotalDue, 2), $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        // payment
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => 'order',
                'external_id' => $transactionId,
                'external_provider' => 'paypal',
                'status' => 'paid',
                'message' => '',
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // shippingAddress
        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'type' => ConfigService::$shippingAddressType,
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'first_name' => $orderData['shipping-first-name'],
                'last_name' => $orderData['shipping-last-name'],
                'street_line_1' => $orderData['shipping-address-line-1'],
                'street_line_2' => null,
                'city' => $orderData['shipping-city'],
                'zip' => $orderData['shipping-zip-or-postal-code'],
                'state' => $orderData['shipping-region'],
                'country' => $orderData['shipping-country'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // order & based order prices
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'total_due' => round($expectedOrderTotalDue, 2),
                'product_due' => $expectedProductDue,
                'taxes_due' => round($expectedTaxes, 2),
                'shipping_due' => $shippingCostAmount,
                'finance_due' => null,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => ConfigService::$brand,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // orderItem
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'weight' => $productOne['weight'],
                'initial_price' => $productOne['price'],
                'total_discounted' => $expectedProductOneDiscountAmount,
                'final_price' => $expectedProductOneDiscountedPrice,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'weight' => $productTwo['weight'],
                'initial_price' => $productTwo['price'],
                'total_discounted' => $expectedProductOneDiscountAmount,
                'final_price' => $expectedProductTwoTotalPrice,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // orderItemFulfillment
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }
}
