<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
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
            'payment_method_type' => PaymentMethod::TYPE_PAYPAL,
            'billing_region' => $this->faker->word,
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => 'Canada',
            'company_name' => $this->faker->creditCardType,
            'gateway' => ConfigService::$brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => 'ab',
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => 'Canada',
            'currency' => $currency
        ];

        $session = $this->app->make(Store::class);

        $session->flush();

        $this->session(['order-form-input' => $orderData]);

        $shippingCountry = 'canada';

        $shippingState = $this->faker->randomElement(array_keys(ConfigService::$taxRate[$shippingCountry]));

        $shippingAddress = new Address();
        $shippingAddress->setCountry($shippingCountry)
            ->setState($shippingState);

        $billingCountry = 'canada';
        $billingState = $this->faker->randomElement(array_keys(ConfigService::$taxRate[$billingCountry]));

        $billingAddress = new Address();
        $billingAddress
            ->setCountry($billingCountry)
            ->setState($billingState);

        $shippingOption = $this->fakeShippingOption([
            'country' => $shippingCountry,
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

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductOneDiscountAmount = round($discount['amount'] * $productOneQuantity, 2);

        $expectedProductOneDiscountedPrice = round($expectedProductOneTotalPrice - $expectedProductOneDiscountAmount, 2);

        $productTwoQuantity = 1;

        $expectedProductTwoTotalPrice = round($productTwo['price'] * $productTwoQuantity, 2);

        $expectedProductTwoDiscountedPrice = 0;

        $expectedProductDue = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice;

        $expectedTotalFromItems = round($expectedProductOneDiscountedPrice + $expectedProductTwoTotalPrice, 2);

        $taxService = $this->app->make(TaxService::class);

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = $expectedTotalFromItems * $taxRate + $shippingCostAmount * $taxRate;

        $expectedOrderTotalDue = $expectedTotalFromItems + $shippingCostAmount + $expectedTaxes;

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);

        $cart->setItem(new CartItem($productOne['sku'], $productOneQuantity));
        $cart->setItem(new CartItem($productTwo['sku'], $productTwoQuantity));

        $cart->toSession();

        $billingAgreementId = rand(1,100);

        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn($billingAgreementId);

        $transactionId = rand(1,100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        ConfigService::$paypalAgreementFulfilledRoute = 'order.submit.paypal';

        $paypalToken = $this->faker->word;

        $this->entityManager->clear();

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
                'zip' => $orderData['billing_zip_or_postal_code'],
                'state' => $orderData['billing_region'],
                'country' => $orderData['billing_country'],
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
                'method_type' => PaymentMethod::TYPE_PAYPAL,
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
                'first_name' => $orderData['shipping_first_name'],
                'last_name' => $orderData['shipping_last_name'],
                'street_line_1' => $orderData['shipping_address_line_1'],
                'street_line_2' => null,
                'city' => $orderData['shipping_city'],
                'zip' => $orderData['shipping_zip_or_postal_code'],
                'state' => $orderData['shipping_region'],
                'country' => $orderData['shipping_country'],
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
