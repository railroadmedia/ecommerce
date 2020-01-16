<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\CurrencyService;
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

        $country = 'Canada';
        $region = 'alberta';

        $orderData = [
            'payment_method_type' => PaymentMethod::TYPE_PAYPAL,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => $country,
            'company_name' => $this->faker->creditCardType,
            'gateway' => config('ecommerce.brand'),
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'currency' => $currency
        ];

        $session = $this->app->make(Store::class);

        $session->flush();

        $this->session(['order-form-input' => $orderData]);

        $shippingAddress = new Address();
        $shippingAddress->setCountry($country);
        $shippingAddress->setRegion($region);

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $shippingOption = $this->fakeShippingOption([
            'country' => $country,
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
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 0.20,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
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
            'products_relation_type' => DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY,
            'type' => 'product quantity requirement',
            'min' => '1',
            'max' => '2000000',
        ]);

        $discountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'discount_criteria_id' => $discountCriteria['id'],
            'product_id' => $productOne['id'],
        ]);

        $productOneQuantity = 1;

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductOneDiscountAmount = round($discount['amount'] * $productOneQuantity, 2);

        $expectedProductOneDiscountedPrice = round($expectedProductOneTotalPrice - $expectedProductOneDiscountAmount, 2);

        $productTwoQuantity = 1;

        $expectedProductTwoTotalPrice = round($productTwo['price'] * $productTwoQuantity, 2);

        $expectedTotalFromItems = round($expectedProductOneDiscountedPrice + $expectedProductTwoTotalPrice, 2);

        $expectedTaxRateProduct =
            config('ecommerce.product_tax_rate')[strtolower($orderData['shipping_country'])][strtolower(
                $orderData['shipping_region']
            )];
        $expectedTaxRateShipping =
            config('ecommerce.shipping_tax_rate')[strtolower($orderData['shipping_country'])][strtolower(
                $orderData['shipping_region']
            )];

        $expectedTaxes = round($expectedTaxRateProduct * $expectedTotalFromItems
            + $expectedTaxRateShipping * $shippingCostAmount, 2);

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService
            ->convertFromBase($expectedOrderTotalDue, $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

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

        config()->set('ecommerce.paypal.agreement_fulfilled_path', 'order.submit.paypal');

        $paypalToken = $this->faker->word;

        $this->entityManager->clear();

        $response = $this->call(
            'GET',
            '/order-form/submit-paypal',
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
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
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
            'ecommerce_addresses',
            [
                'type' => \Railroad\Ecommerce\Entities\Address::BILLING_ADDRESS_TYPE,
                'brand' => config('ecommerce.brand'),
                'user_id' => $userId,
                'zip' => $orderData['billing_zip_or_postal_code'],
                'region' => $orderData['billing_region'],
                'country' => $orderData['billing_country'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // billingAgreement
        $this->assertDatabaseHas(
            'ecommerce_paypal_billing_agreements',
            [
                'external_id' => $billingAgreementId,
                'payment_gateway_name' => config('ecommerce.brand'),
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // paymentMethod
        $this->assertDatabaseHas(
            'ecommerce_payment_methods',
            [
                'paypal_billing_agreement_id' => 1,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // userPaymentMethods
        $this->assertDatabaseHas(
            'ecommerce_user_payment_methods',
            [
                'user_id' => $userId,
                'is_primary' => true,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // payment
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $transactionId,
                'external_provider' => 'paypal',
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // shippingAddress
        $this->assertDatabaseHas(
            'ecommerce_addresses',
            [
                'type' => \Railroad\Ecommerce\Entities\Address::SHIPPING_ADDRESS_TYPE,
                'brand' => config('ecommerce.brand'),
                'user_id' => $userId,
                'first_name' => $orderData['shipping_first_name'],
                'last_name' => $orderData['shipping_last_name'],
                'street_line_1' => $orderData['shipping_address_line_1'],
                'street_line_2' => null,
                'city' => $orderData['shipping_city'],
                'zip' => $orderData['shipping_zip_or_postal_code'],
                'region' => $orderData['shipping_region'],
                'country' => $orderData['shipping_country'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // order & based order prices
        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => round($expectedOrderTotalDue, 2),
                'product_due' => $expectedTotalFromItems,
                'taxes_due' => round($expectedTaxes, 2),
                'shipping_due' => $shippingCostAmount,
                'finance_due' => 0,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => config('ecommerce.brand'),
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // orderItem
        $this->assertDatabaseHas(
            'ecommerce_order_items',
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
            'ecommerce_order_items',
            [
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'weight' => $productTwo['weight'],
                'initial_price' => $productTwo['price'],
                'total_discounted' => 0,
                'final_price' => $expectedProductTwoTotalPrice,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // orderItemFulfillment
        $this->assertDatabaseHas(
            'ecommerce_order_item_fulfillment',
            [
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_existing_active_subs_get_cancelled_on_lifetime_purchase()
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();

        $country = 'Canada';
        $region = 'alberta';

        $orderData = [
            'payment_method_type' => PaymentMethod::TYPE_PAYPAL,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => $country,
            'company_name' => $this->faker->creditCardType,
            'gateway' => config('ecommerce.brand'),
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'currency' => $currency
        ];

        $session = $this->app->make(Store::class);

        $session->flush();

        $this->session(['order-form-input' => $orderData]);

        $shippingAddress = new Address();
        $shippingAddress->setCountry($country);
        $shippingAddress->setRegion($region);

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $subscriptionProduct = $this->fakeProduct([
            'price' => 12.95,
            'sku' => 'DRUMEO_MEMBERSHIP_RECURRING_YEARLY',
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
            'brand' => 'drumeo',
        ]);

        $lifetimeProduct = $this->fakeProduct([
            'price' => 247,
            'sku' => 'DRUMEO_MEMBERSHIP_LIFETIME',
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'brand' => 'drumeo',
        ]);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);

        $cart->setItem(new CartItem($subscriptionProduct['sku'], 1));

        $cart->toSession();

        $billingAgreementId = rand(1,100);

        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn($billingAgreementId);

        $transactionId = rand(1,100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        config()->set('ecommerce.paypal.agreement_fulfilled_path', 'order.submit.paypal');

        $paypalToken = $this->faker->word;

        $this->entityManager->clear();

        $response = $this->call(
            'GET',
            '/order-form/submit-paypal',
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
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $subscriptionProduct['id'],
                'quantity' => 1,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // order & based order prices
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'product_id' => $subscriptionProduct['id'],
                'is_active' => true,
                'user_id' => $userId,
            ]
        );

        // now that we have a active recurring sub, order a lifetime and make sure it gets cancelled

        $cart = new Cart();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);

        $cart->setItem(new CartItem($lifetimeProduct['sku'], 1));

        $cart->toSession();

        $this->cartService->setCart($cart);

        $this->session(['order-form-input' => $orderData]);

        $response = $this->call(
            'GET',
            '/order-form/submit-paypal',
            ['token' => $paypalToken, 'payment_method_type' => 'paypal']
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'product_id' => $subscriptionProduct['id'],
                'is_active' => false,
                'canceled_on' => Carbon::now()->toDateTimeString(),
                'user_id' => $userId,
            ]
        );
    }

    public function test_existing_active_subs_get_cancelled_on_new_sub_purchase_and_date_is_extended()
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();

        $country = 'Canada';
        $region = 'alberta';

        $orderData = [
            'payment_method_type' => PaymentMethod::TYPE_PAYPAL,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => $country,
            'company_name' => $this->faker->creditCardType,
            'gateway' => config('ecommerce.brand'),
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'currency' => $currency
        ];

        $session = $this->app->make(Store::class);

        $session->flush();

        $this->session(['order-form-input' => $orderData]);

        $shippingAddress = new Address();
        $shippingAddress->setCountry($country);
        $shippingAddress->setRegion($region);

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $subscriptionProduct1 = $this->fakeProduct([
            'price' => 12.95,
            'sku' => 'DRUMEO_MEMBERSHIP_RECURRING_MONTHLY',
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => config('ecommerce.interval_type_monthly'),
            'subscription_interval_count' => 1,
            'brand' => 'drumeo',
        ]);

        $subscriptionProduct2 = $this->fakeProduct([
            'price' => 222.95,
            'sku' => 'DRUMEO_MEMBERSHIP_RECURRING_YEARLY',
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
            'brand' => 'drumeo',
        ]);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);

        $cart->setItem(new CartItem($subscriptionProduct1['sku'], 1));

        $cart->toSession();

        $billingAgreementId = rand(1,100);

        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn($billingAgreementId);

        $transactionId = rand(1,100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        config()->set('ecommerce.paypal.agreement_fulfilled_path', 'order.submit.paypal');

        $paypalToken = $this->faker->word;

        $this->entityManager->clear();

        $response = $this->call(
            'GET',
            '/order-form/submit-paypal',
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
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $subscriptionProduct1['id'],
                'quantity' => 1,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // order & based order prices
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'product_id' => $subscriptionProduct1['id'],
                'is_active' => true,
                'user_id' => $userId,
                'paid_until' => Carbon::now()->addMonth()->toDateTimeString(),
            ]
        );

        // now that we have a active recurring sub, order another
        // and make sure the dates are adjusted and the old one is cancelled

        Carbon::setTestNow(Carbon::now()->addMinute());

        $cart = new Cart();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);

        $cart->setItem(new CartItem($subscriptionProduct2['sku'], 1));

        $cart->toSession();

        $this->cartService->setCart($cart);

        $this->session(['order-form-input' => $orderData]);

        $response = $this->call(
            'GET',
            '/order-form/submit-paypal',
            ['token' => $paypalToken, 'payment_method_type' => 'paypal']
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'product_id' => $subscriptionProduct1['id'],
                'is_active' => false,
                'canceled_on' => Carbon::now()->toDateTimeString(),
                'user_id' => $userId,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'product_id' => $subscriptionProduct2['id'],
                'is_active' => true,
                'canceled_on' => null,
                'user_id' => $userId,
                'paid_until' => Carbon::now()->addMonth()->addYear()->subMinute()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $subscriptionProduct2['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()->addMonth()->addYear()->subMinute()
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))->toDateTimeString()
            ]
        );
    }

    public function test_payment_plan_not_cancelled_on_new_membership_sub_purchase()
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();

        $country = 'Canada';
        $region = 'alberta';

        $orderData = [
            'payment_method_type' => PaymentMethod::TYPE_PAYPAL,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->postcode,
            'billing_country' => $country,
            'company_name' => $this->faker->creditCardType,
            'gateway' => config('ecommerce.brand'),
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->address,
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'currency' => $currency,
        ];

        $session = $this->app->make(Store::class);

        $session->flush();

        $this->session(
            [
                'order-form-input' => array_merge($orderData, ['payment_plan_number_of_payments' => 2])
            ]
        );

        $shippingAddress = new Address();
        $shippingAddress->setCountry($country);
        $shippingAddress->setRegion($region);

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $packProduct = $this->fakeProduct([
            'price' => 100.95,
            'sku' => 'DRUMEO_PACK_PRODUCT',
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'brand' => 'drumeo',
        ]);

        $subscriptionProduct2 = $this->fakeProduct([
            'price' => 222.95,
            'sku' => 'DRUMEO_MEMBERSHIP_RECURRING_YEARLY',
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
            'brand' => 'drumeo',
        ]);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);

        $cart->setItem(new CartItem($packProduct['sku'], 1));

        $cart->toSession();

        $billingAgreementId = rand(1,100);

        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn($billingAgreementId);

        $transactionId = rand(1,100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        config()->set('ecommerce.paypal.agreement_fulfilled_path', 'order.submit.paypal');

        $paypalToken = $this->faker->word;

        $this->entityManager->clear();

        $response = $this->call(
            'GET',
            '/order-form/submit-paypal',
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
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $packProduct['id'],
                'quantity' => 1,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // order & based order prices
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'product_id' => null,
                'type' => 'payment plan',
                'is_active' => true,
                'user_id' => $userId,
                'paid_until' => Carbon::now()->addMonth()->toDateTimeString(),
            ]
        );

        // now that we have a active recurring sub, order another
        // and make sure the dates are adjusted and the old one is cancelled

        Carbon::setTestNow(Carbon::now()->addMinute());

        $cart = new Cart();

        $cart->setShippingAddress($shippingAddress);
        $cart->setBillingAddress($billingAddress);

        $cart->setItem(new CartItem($subscriptionProduct2['sku'], 1));

        $cart->toSession();

        $this->cartService->setCart($cart);

        $this->session(['order-form-input' => $orderData]);

        $response = $this->call(
            'GET',
            '/order-form/submit-paypal',
            ['token' => $paypalToken, 'payment_method_type' => 'paypal']
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'product_id' => null,
                'type' => 'payment plan',
                'is_active' => true,
                'user_id' => $userId,
                'paid_until' => Carbon::now()->subMinute()->addMonth()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'product_id' => $subscriptionProduct2['id'],
                'is_active' => true,
                'canceled_on' => null,
                'user_id' => $userId,
                'paid_until' => Carbon::now()->addYear()->toDateTimeString(),
            ]
        );
    }


}
