<?php

namespace Railroad\Ecommerce\Tests\Functional\Ordering;

use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\SubscriptionService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class OrderValuesTest extends EcommerceTestCase
{
    use WithoutMiddleware;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var CartAddressService
     */
    protected $cartAddressService;

    /**
     * @var TaxService
     */
    protected $taxService;

    /**
     * @var SubscriptionService
     */
    protected $subscriptionService;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var MockObject|AuthManager
     */
    protected $authManagerMock;

    /**
     * @var MockObject|SessionGuard
     */
    protected $sessionGuardMock;

    protected $brand = 'drumeo';
    protected $currency = 'USD';

    protected function setUp()
    {
        parent::setUp();

        $this->cartService = $this->app->make(CartService::class);
        $this->cartAddressService = $this->app->make(CartAddressService::class);
        $this->taxService = $this->app->make(TaxService::class);
        $this->subscriptionService = $this->app->make(SubscriptionService::class);
        $this->subscriptionRepository = $this->app->make(SubscriptionRepository::class);

        config()->set('ecommerce.brand', $this->brand);
    }

    /**
     * @param $type
     * @param $price
     * @param int $weight // null or zero for digital, more than zero for physical
     * @param null $subscriptionIntervalType
     * @param null $subscriptionIntervalCount
     * @return array
     */
    protected function newProduct(
        $type,
        $price,
        $weight = 0,
        $subscriptionIntervalType = null,
        $subscriptionIntervalCount = null
    )
    {
        return $this->fakeProduct(
            [
                'sku' => 'product-' . rand() . rand(),
                'price' => $price,
                'type' => $type,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => $weight > 0,
                'weight' => $weight,
                'subscription_interval_type' => $subscriptionIntervalType,
                'subscription_interval_count' => $subscriptionIntervalCount,
            ]
        );
    }

    /**
     * @param $country
     * @param $cost
     * @param int[] $weightRange
     */
    public function newShippingOptionCost($country, $cost, $weightRange = [0, 100])
    {
        $shippingOption = $this->fakeShippingOption(
            [
                'country' => $country,
                'active' => 1,
                'priority' => 1,
            ]
        );

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => $weightRange[0],
                'max' => $weightRange[1],
                'price' => $cost,
            ]
        );
    }

    /**
     * @param $fingerPrint
     */
    protected function newStripePaymentMocks($fingerPrint = null)
    {
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $fakerCustomer->id = $this->faker->word . rand();
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);
        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint ?? $this->faker->word . $this->faker->randomNumber(6);
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $fakerCard->customer = $fakerCustomer->id;
        $fakerCard->name = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);
    }

    public function test_basic_order_no_taxes_or_shipping()
    {
        $productCost = 100;
        $country = 'United States';
        $region = 'Ohio';

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();

        $product = $this->newProduct(Product::TYPE_DIGITAL_ONE_TIME, $productCost);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
//            'shipping_first_name' => $this->faker->firstName,
//            'shipping_last_name' => $this->faker->lastName,
//            'shipping_address_line_1' => $this->faker-$this->faker->words(3, true)
//            'shipping_city' => $this->faker->city,
//            'shipping_region' => $region,
//            'shipping_zip_or_postal_code' => $this->faker->postcode,
//            'shipping_country' => $country,
            'payment_plan_number_of_payments' => 1,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscriptions',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $productCost,
                'product_due' => $productCost,
                'taxes_due' => 0,
                'shipping_due' => 0,
                'finance_due' => 0,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $productCost,
                'total_paid' => $productCost,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payment_taxes',
            [
                'id' => 1,
            ]
        );
    }

    public function test_basic_order_no_taxes_or_shipping_with_payment_plan()
    {
        $productCost = 100;
        $country = 'United States';
        $region = 'Ohio';
        $numberOfPayments = 5;
        $financeCosts = 1;
        $paymentPlanCostPerPayment = round(($productCost + $financeCosts) / $numberOfPayments, 2);

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();

        $product = $this->newProduct(Product::TYPE_DIGITAL_ONE_TIME, $productCost);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
//            'shipping_first_name' => $this->faker->firstName,
//            'shipping_last_name' => $this->faker->lastName,
//            'shipping_address_line_1' => $this->faker-$this->faker->words(3, true)
//            'shipping_city' => $this->faker->city,
//            'shipping_region' => $region,
//            'shipping_zip_or_postal_code' => $this->faker->postcode,
//            'shipping_country' => $country,
            'payment_plan_number_of_payments' => $numberOfPayments,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'product_id' => null,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'total_cycles_paid' => 1,
                'interval_type' => 'month',
                'interval_count' => 1,
                'total_price' => $paymentPlanCostPerPayment,
                'tax' => 0,
                'canceled_on' => null
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $productCost + $financeCosts,
                'total_paid' => $paymentPlanCostPerPayment,
                'product_due' => $productCost,
                'taxes_due' => 0,
                'shipping_due' => 0,
                'finance_due' => $financeCosts,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $paymentPlanCostPerPayment,
                'total_paid' => $paymentPlanCostPerPayment,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payment_taxes',
            [
                'id' => 1,
            ]
        );
    }

    public function test_basic_order_no_taxes_or_shipping_with_payment_plan_uneven_payment_split_too_much()
    {
        $productCost = 98.33;
        $country = 'United States';
        $region = 'Ohio';
        $numberOfPayments = 5;
        $financeCosts = 1;

        $grandTotalDue = $productCost + $financeCosts; // 99.33

        // If the rounded cost per payment doesn't exactly add up to the grand total we must modify the inital payment
        // to make sure the math all adds up.
        $paymentPlanCostPerPayment = round(($productCost + $financeCosts) / $numberOfPayments, 2); // 19.866 => 19.87
        $initialPaymentAmount = round(($productCost + $financeCosts) / $numberOfPayments, 2); // 19.866 => 19.87

        $difference =
            round(
                $grandTotalDue - ($initialPaymentAmount + $paymentPlanCostPerPayment * 4),
                2
            ); // 99.33 - 99.35 = -0.02
        $initialPaymentAmount += $difference; // 19.85

        $this->assertEquals(19.85, $initialPaymentAmount);
        $this->assertEquals($grandTotalDue, ($initialPaymentAmount + ($paymentPlanCostPerPayment * 4)));

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();

        $product = $this->newProduct(Product::TYPE_DIGITAL_ONE_TIME, $productCost);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
//            'shipping_first_name' => $this->faker->firstName,
//            'shipping_last_name' => $this->faker->lastName,
//            'shipping_address_line_1' => $this->faker-$this->faker->words(3, true)
//            'shipping_city' => $this->faker->city,
//            'shipping_region' => $region,
//            'shipping_zip_or_postal_code' => $this->faker->postcode,
//            'shipping_country' => $country,
            'payment_plan_number_of_payments' => $numberOfPayments,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'product_id' => null,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'total_cycles_paid' => 1,
                'interval_type' => 'month',
                'interval_count' => 1,
                'total_price' => $paymentPlanCostPerPayment,
                'tax' => 0,
                'canceled_on' => null
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $productCost + $financeCosts,
                'total_paid' => $initialPaymentAmount,
                'product_due' => $productCost,
                'taxes_due' => 0,
                'shipping_due' => 0,
                'finance_due' => $financeCosts,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $initialPaymentAmount,
                'total_paid' => $initialPaymentAmount,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payment_taxes',
            [
                'id' => 1,
            ]
        );
    }

    public function test_basic_order_no_taxes_or_shipping_with_payment_plan_uneven_payment_split_too_little()
    {
        $productCost = 98.67;
        $country = 'United States';
        $region = 'Ohio';
        $numberOfPayments = 5;
        $financeCosts = 1;

        $grandTotalDue = $productCost + $financeCosts; // 99.33

        // If the rounded cost per payment doesn't exactly add up to the grand total we must modify the inital payment
        // to make sure the math all adds up.
        $paymentPlanCostPerPayment = round(($productCost + $financeCosts) / $numberOfPayments, 2); // 19.934 => 19.93
        $initialPaymentAmount = round(($productCost + $financeCosts) / $numberOfPayments, 2); // 19.934 => 19.93

        $difference =
            round($grandTotalDue - ($initialPaymentAmount + $paymentPlanCostPerPayment * 4), 2); // 99.67 - 99.65 = 0.02

        $initialPaymentAmount += $difference; // 19.95

        $this->assertEquals(19.95, $initialPaymentAmount);
        $this->assertEquals($grandTotalDue, ($initialPaymentAmount + ($paymentPlanCostPerPayment * 4)));

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();

        $product = $this->newProduct(Product::TYPE_DIGITAL_ONE_TIME, $productCost);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
//            'shipping_first_name' => $this->faker->firstName,
//            'shipping_last_name' => $this->faker->lastName,
//            'shipping_address_line_1' => $this->faker-$this->faker->words(3, true)
//            'shipping_city' => $this->faker->city,
//            'shipping_region' => $region,
//            'shipping_zip_or_postal_code' => $this->faker->postcode,
//            'shipping_country' => $country,
            'payment_plan_number_of_payments' => $numberOfPayments,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'product_id' => null,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'total_cycles_paid' => 1,
                'interval_type' => 'month',
                'interval_count' => 1,
                'total_price' => $paymentPlanCostPerPayment,
                'tax' => 0,
                'canceled_on' => null
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $productCost + $financeCosts,
                'total_paid' => $initialPaymentAmount,
                'product_due' => $productCost,
                'taxes_due' => 0,
                'shipping_due' => 0,
                'finance_due' => $financeCosts,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $initialPaymentAmount,
                'total_paid' => $initialPaymentAmount,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payment_taxes',
            [
                'id' => 1,
            ]
        );
    }

    public function test_basic_order_no_taxes_with_shipping()
    {
        $shippingCost = 5;
        $productCost = 100;
        $country = 'United States';
        $region = 'Ohio';

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();
        $this->newShippingOptionCost($country, $shippingCost);

        $product = $this->newProduct(Product::TYPE_PHYSICAL_ONE_TIME, $productCost, 5);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->words(3, true),
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'payment_plan_number_of_payments' => 1,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscriptions',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $productCost + $shippingCost,
                'product_due' => $productCost,
                'taxes_due' => 0,
                'shipping_due' => $shippingCost,
                'finance_due' => 0,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_basic_order_no_taxes_with_shipping_with_payment_plan()
    {
        $productCost = 100;
        $shippingCost = 5;
        $country = 'United States';
        $region = 'Ohio';
        $numberOfPayments = 5;
        $financeCosts = 1;
        $paymentPlanCostPerPayment = round(($productCost + $financeCosts) / $numberOfPayments, 2);
        $initialPaymentAmount = round((($productCost + $financeCosts) / $numberOfPayments) + $shippingCost, 2);
        $grandTotalDue = $productCost + $financeCosts + $shippingCost;

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();
        $this->newShippingOptionCost($country, $shippingCost);

        $product = $this->newProduct(Product::TYPE_PHYSICAL_ONE_TIME, $productCost, 5);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->words(3, true),
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'payment_plan_number_of_payments' => $numberOfPayments,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'product_id' => null,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'total_cycles_paid' => 1,
                'total_cycles_due' => $numberOfPayments,
                'interval_type' => 'month',
                'interval_count' => 1,
                'total_price' => $paymentPlanCostPerPayment,
                'tax' => 0,
                'currency' => $this->currency,
                'canceled_on' => null
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $grandTotalDue,
                'total_paid' => $initialPaymentAmount,
                'product_due' => $productCost,
                'taxes_due' => 0,
                'shipping_due' => $shippingCost,
                'finance_due' => $financeCosts,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $initialPaymentAmount,
                'total_paid' => $initialPaymentAmount,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                "country" => $country,
                "region" => $region,
                "product_rate" => 0,
                "shipping_rate" => 0,
                "product_taxes_paid" => 0,
                "shipping_taxes_paid" => 0,
            ]
        );

        // make sure the payment is linked properly
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 1,
            ]
        );
    }

    public function test_basic_order_with_taxes_with_shipping_alberta()
    {
        $shippingCost = 5;
        $productCost = 100;
        $country = 'Canada';
        $region = 'Alberta';
        $address = new Address($country, $region);

        $productTaxRate = $this->taxService->getProductTaxRate($address);
        $shippingTaxRate = $this->taxService->getShippingTaxRate($address);

        $productTaxesDue = round($productCost * $productTaxRate, 2);
        $shippingTaxesDue = round($shippingCost * $shippingTaxRate, 2);

        $grandTotalDue = round($productCost + $shippingCost + $productTaxesDue + $shippingTaxesDue, 2);

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();
        $this->newShippingOptionCost($country, $shippingCost);

        $product = $this->newProduct(Product::TYPE_PHYSICAL_ONE_TIME, $productCost, 5);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->words(3, true),
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'payment_plan_number_of_payments' => 1,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscriptions',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $grandTotalDue,
                'product_due' => $productCost,
                'taxes_due' => $productTaxesDue + $shippingTaxesDue,
                'shipping_due' => $shippingCost,
                'finance_due' => 0,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscriptions',
            ['id' => 1]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $grandTotalDue,
                'total_paid' => $grandTotalDue,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                "country" => $country,
                "region" => $region,
                "product_rate" => $productTaxRate,
                "shipping_rate" => $shippingTaxRate,
                "product_taxes_paid" => $productTaxesDue,
                "shipping_taxes_paid" => $shippingTaxesDue,
            ]
        );
    }

    public function test_basic_order_with_taxes_with_shipping_bc()
    {
        $shippingCost = 5;
        $productCost = 100;
        $country = 'Canada';
        $region = 'British Columbia';
        $address = new Address($country, $region);

        $productTaxRate = $this->taxService->getProductTaxRate($address);
        $shippingTaxRate = $this->taxService->getShippingTaxRate($address);

        $productTaxesDue = round($productCost * $productTaxRate, 2);
        $shippingTaxesDue = round($shippingCost * $shippingTaxRate, 2);

        $grandTotalDue = round($productCost + $shippingCost + $productTaxesDue + $shippingTaxesDue, 2);

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();
        $this->newShippingOptionCost($country, $shippingCost);

        $product = $this->newProduct(Product::TYPE_PHYSICAL_ONE_TIME, $productCost, 5);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->words(3, true),
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'payment_plan_number_of_payments' => 1,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscriptions',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $grandTotalDue,
                'product_due' => $productCost,
                'taxes_due' => $productTaxesDue + $shippingTaxesDue,
                'shipping_due' => $shippingCost,
                'finance_due' => 0,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscriptions',
            ['id' => 1]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $grandTotalDue,
                'total_paid' => $grandTotalDue,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                "country" => $country,
                "region" => $region,
                "product_rate" => $productTaxRate,
                "shipping_rate" => $shippingTaxRate,
                "product_taxes_paid" => $productTaxesDue,
                "shipping_taxes_paid" => $shippingTaxesDue,
            ]
        );

        // hard code test just in case...
        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                "country" => $country,
                "region" => $region,
                "product_rate" => 0.12,
                "shipping_rate" => 0.05,
                "product_taxes_paid" => 12,
                "shipping_taxes_paid" => 0.25,
            ]
        );
    }

    public function test_basic_order_with_taxes_with_shipping_with_payment_plan_alberta()
    {
        $productCost = 100;
        $shippingCost = 10;
        $country = 'Canada';
        $region = 'Alberta';
        $numberOfPayments = 5;
        $financeCosts = 1;
        $address = new Address($country, $region);

        $productTaxRate = $this->taxService->getProductTaxRate($address); // 0.05
        $shippingTaxRate = $this->taxService->getShippingTaxRate($address); // 0.05

        $productTaxesDue = round($productCost * $productTaxRate, 2); // 5.0
        $shippingTaxesDue = round($shippingCost * $shippingTaxRate, 2); // 0.50

        // initial payment amount = ((100 + 5 + 1) / 5) + 10 + 0.50 = 31.7
        // initial payment amount fixed =
        // recurring amount = (100 + 1) / 5 = 20.2

        // check = ((20.2 * 1.05) * 4) + 31.7 = 116.54 // its 4 too many from rounding

        $paymentPlanCostPerPayment = round(($productCost + $financeCosts) / $numberOfPayments, 2); // 20.2
        $paymentPlanCostPerPaymentAfterTax = round($paymentPlanCostPerPayment * (1+$productTaxRate), 2); // 20.2
        $initialPaymentAmount =
            round(
                $paymentPlanCostPerPaymentAfterTax +
                $shippingCost +
                $shippingTaxesDue,
                2
            ); // 33.1
        $grandTotalDue = $productCost + $financeCosts + $shippingCost + $productTaxesDue + $shippingTaxesDue; // 123.5

        $difference =
            round(
                $grandTotalDue -
                ($initialPaymentAmount + round($paymentPlanCostPerPayment * (1 + $productTaxRate) * 4, 2)),
                2
            ); // 116.5 - 116.54 = -0.04

        $initialPaymentAmount += $difference; // 31.66

        $this->assertEquals(31.66, $initialPaymentAmount);
        $this->assertEquals(
            $grandTotalDue,
            ($initialPaymentAmount + round($paymentPlanCostPerPayment * (1 + $productTaxRate) * 4, 2))
        );

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();
        $this->newShippingOptionCost($country, $shippingCost);

        $product = $this->newProduct(Product::TYPE_PHYSICAL_ONE_TIME, $productCost, 5);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->words(3, true),
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'payment_plan_number_of_payments' => $numberOfPayments,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $grandTotalDue,
                'total_paid' => $initialPaymentAmount,
                'product_due' => $productCost,
                'taxes_due' => $productTaxesDue + $shippingTaxesDue,
                'shipping_due' => $shippingCost,
                'finance_due' => $financeCosts,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'product_id' => null,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'total_cycles_paid' => 1,
                'total_cycles_due' => $numberOfPayments,
                'interval_type' => 'month',
                'interval_count' => 1,
                'total_price' => $paymentPlanCostPerPayment,
                'tax' => 0,
                'currency' => $this->currency,
                'canceled_on' => null
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $initialPaymentAmount,
                'total_paid' => $initialPaymentAmount,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                "country" => $country,
                "region" => $region,
                "product_rate" => 0.05,
                "shipping_rate" => 0.05,
                "product_taxes_paid" => 5,
                "shipping_taxes_paid" => 0.5,
            ]
        );

        // simulate renewals
        $result = $this->subscriptionService->renew($this->subscriptionRepository->find(1));
        $result = $this->subscriptionService->renew($this->subscriptionRepository->find(1));
        $result = $this->subscriptionService->renew($this->subscriptionRepository->find(1));
        $result = $this->subscriptionService->renew($this->subscriptionRepository->find(1));

        // verify grand total in the DB
        $payments = $this->databaseManager->table('ecommerce_payments')
            ->get();

        $totalPaid = 0;

        foreach ($payments as $payment) {
            $totalPaid += $payment->total_paid;
        }

        $this->assertEquals($grandTotalDue, round($totalPaid, 2));

        // make sure the payment is linked properly
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 1,
            ]
        );
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 2,
            ]
        );
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 3,
            ]
        );
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 4,
            ]
        );
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 5,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 2,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 3,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 4,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 5,
            ]
        );

        // make sure order total paid is updated
        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $grandTotalDue,
                'total_paid' => $grandTotalDue,
                'product_due' => $productCost,
                'taxes_due' => $productTaxesDue + $shippingTaxesDue,
                'shipping_due' => $shippingCost,
                'finance_due' => $financeCosts,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_basic_order_with_taxes_with_shipping_with_payment_plan_bc()
    {
        $productCost = 100;
        $shippingCost = 10;
        $country = 'Canada';
        $region = 'British Columbia';
        $numberOfPayments = 5;
        $financeCosts = 1;
        $address = new Address($country, $region);

        $productTaxRate = $this->taxService->getProductTaxRate($address); // 0.12
        $shippingTaxRate = $this->taxService->getShippingTaxRate($address); // 0.05

        $productTaxesDue = round($productCost * $productTaxRate, 2); // 12.0
        $shippingTaxesDue = round($shippingCost * $shippingTaxRate, 2); // 0.50

        // grand total due = 100 + 10 + 12 + 0.50 + 1 = 123.5

        // recurring amount pre tax = (100 + 1) / 5 = 20.2
        // recurring amount post tax = 20.2 * 0.12 = 24.24
        // total after payments & tax = 24.24 * 4 = 96.96

        // initial payment amount = 24.24 + 10 + 0.50 = 34.74

        // check = ((20.2 * 1.12) * 4) + 33.10 = 116.54 // its 4 too many from rounding

        $paymentPlanCostPerPayment = round(($productCost + $financeCosts) / $numberOfPayments, 2); // 20.2
        $paymentPlanCostPerPaymentAfterTax = round($paymentPlanCostPerPayment * (1+$productTaxRate), 2); // 20.2
        $initialPaymentAmount =
            round(
                $paymentPlanCostPerPaymentAfterTax +
                $shippingCost +
                $shippingTaxesDue,
                2
            ); // 33.1
        $grandTotalDue = $productCost + $financeCosts + $shippingCost + $productTaxesDue + $shippingTaxesDue; // 123.5

        $difference =
            round(
                $grandTotalDue -
                ($initialPaymentAmount + round($paymentPlanCostPerPaymentAfterTax * 4, 2)),
                2
            ); // -0.01

        $initialPaymentAmount += $difference; // 33.02

        $this->assertEquals(33.02, $initialPaymentAmount);
        $this->assertEquals(
            $grandTotalDue,
            ($initialPaymentAmount + round($paymentPlanCostPerPaymentAfterTax * 4, 2))
        );

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();
        $this->newShippingOptionCost($country, $shippingCost);

        $product = $this->newProduct(Product::TYPE_PHYSICAL_ONE_TIME, $productCost, 5);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->words(3, true),
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'payment_plan_number_of_payments' => $numberOfPayments,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $grandTotalDue,
                'total_paid' => $initialPaymentAmount,
                'product_due' => $productCost,
                'taxes_due' => $productTaxesDue + $shippingTaxesDue,
                'shipping_due' => $shippingCost,
                'finance_due' => $financeCosts,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'product_id' => null,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'total_cycles_paid' => 1,
                'total_cycles_due' => $numberOfPayments,
                'interval_type' => 'month',
                'interval_count' => 1,
                'total_price' => $paymentPlanCostPerPayment,
                'tax' => 0,
                'currency' => $this->currency,
                'canceled_on' => null
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $initialPaymentAmount,
                'total_paid' => $initialPaymentAmount,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                "country" => $country,
                "region" => $region,
                "product_rate" => 0.12,
                "shipping_rate" => 0.05,
                "product_taxes_paid" => 12,
                "shipping_taxes_paid" => 0.5,
            ]
        );
    }

    public function test_basic_order_with_taxes_with_shipping_with_payment_plan_bc_simulate_renewals()
    {
        $productCost = 100;
        $shippingCost = 10;
        $country = 'Canada';
        $region = 'British Columbia';
        $numberOfPayments = 5;
        $financeCosts = 1;
        $address = new Address($country, $region);

        $productTaxRate = $this->taxService->getProductTaxRate($address); // 0.12
        $shippingTaxRate = $this->taxService->getShippingTaxRate($address); // 0.05

        $productTaxesDue = round($productCost * $productTaxRate, 2); // 12.0
        $shippingTaxesDue = round($shippingCost * $shippingTaxRate, 2); // 0.50

        $costPerPaymentBeforeTaxes = round(($productCost + $financeCosts) / $numberOfPayments, 2); // 20.20
        $costPerPaymentAfterTaxes = round($costPerPaymentBeforeTaxes * (1 + $productTaxRate), 2); // 22.62

        // Customers can only finance the order item price, product taxes, and finance.
        // All shipping costs and shipping taxes must be paid on the first payment.
        $initialPaymentAmount = $costPerPaymentAfterTaxes + $shippingCost + $shippingTaxesDue;
        $recurringPaymentAmount = $costPerPaymentBeforeTaxes;

        $totalAfterPlanIsComplete = $initialPaymentAmount + ($costPerPaymentAfterTaxes * 4);

        $grandTotalDue = $productCost + $financeCosts + $shippingCost + $productTaxesDue + $shippingTaxesDue; // 123.5

        $difference =
            round(
                $grandTotalDue -
                $totalAfterPlanIsComplete,
                2
            ); // -0.01

        $initialPaymentAmount += $difference; // 33.02

        $this->assertEquals(
            $grandTotalDue,
            ($initialPaymentAmount + round($costPerPaymentAfterTaxes * 4, 2))
        );
        $this->assertEquals(33.02, $initialPaymentAmount);

        $userId = $this->createAndLogInNewUser();

        $this->newStripePaymentMocks();
        $this->newShippingOptionCost($country, $shippingCost);

        $product = $this->newProduct(Product::TYPE_PHYSICAL_ONE_TIME, $productCost, 5);

        $this->cartService->addToCart($product['sku'], 1);

        $requestData = [
            'payment_method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'card_token' => $this->faker->word,
            'billing_region' => $region,
            'billing_zip_or_postal_code' => $this->faker->word,
            'billing_country' => $country,
            'gateway' => $this->brand,
            'shipping_first_name' => $this->faker->firstName,
            'shipping_last_name' => $this->faker->lastName,
            'shipping_address_line_1' => $this->faker->words(3, true),
            'shipping_city' => $this->faker->city,
            'shipping_region' => $region,
            'shipping_zip_or_postal_code' => $this->faker->postcode,
            'shipping_country' => $country,
            'payment_plan_number_of_payments' => $numberOfPayments,
            'currency' => $this->currency,
        ];

        $response = $this->call(
            'PUT',
            '/json/order-form/submit',
            $requestData
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $grandTotalDue,
                'total_paid' => $initialPaymentAmount,
                'product_due' => $productCost,
                'taxes_due' => $productTaxesDue + $shippingTaxesDue,
                'shipping_due' => $shippingCost,
                'finance_due' => $financeCosts,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'product_id' => null,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'total_cycles_paid' => 1,
                'total_cycles_due' => $numberOfPayments,
                'interval_type' => 'month',
                'interval_count' => 1,
                'total_price' => $costPerPaymentBeforeTaxes,
                'tax' => 0,
                'currency' => $this->currency,
                'canceled_on' => null
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $initialPaymentAmount,
                'total_paid' => $initialPaymentAmount,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                "id" => 1,
                "country" => $country,
                "region" => $region,
                "product_rate" => 0.12,
                "shipping_rate" => 0.05,
                "product_taxes_paid" => 12,
                "shipping_taxes_paid" => 0.5,
            ]
        );

        // simulate renewals
        $result = $this->subscriptionService->renew($this->subscriptionRepository->find(1));
        $result = $this->subscriptionService->renew($this->subscriptionRepository->find(1));
        $result = $this->subscriptionService->renew($this->subscriptionRepository->find(1));
        $result = $this->subscriptionService->renew($this->subscriptionRepository->find(1));

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'total_cycles_paid' => 5,
                'total_cycles_due' => $numberOfPayments,
                'total_price' => $costPerPaymentBeforeTaxes,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'brand' => $this->brand,
                'user_id' => $userId,
                'is_active' => 1,
                'total_cycles_paid' => 5,
                'total_cycles_due' => $numberOfPayments,
                'total_price' => 20.20,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'id' => 2,
                'total_due' => round($costPerPaymentBeforeTaxes * (1 + $productTaxRate), 2),
                'total_paid' => round($costPerPaymentBeforeTaxes * (1 + $productTaxRate), 2),
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_SUBSCRIPTION_RENEWAL,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'id' => 5,
                'total_due' => 22.62,
                'total_paid' => 22.62,
                'total_refunded' => 0,
                'conversion_rate' => 1.0,
                'type' => Payment::TYPE_SUBSCRIPTION_RENEWAL,
                'external_provider' => 'stripe',
                'status' => Payment::STATUS_PAID,
                'currency' => $this->currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        // verify grand total in the DB
        $payments = $this->databaseManager->table('ecommerce_payments')
            ->get();

        $totalPaid = 0;

        foreach ($payments as $payment) {
            $totalPaid += $payment->total_paid;
        }

        $this->assertEquals($grandTotalDue, round($totalPaid, 2));

        // make sure the payment is linked properly
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 1,
            ]
        );
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 2,
            ]
        );
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 3,
            ]
        );
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 4,
            ]
        );
        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                "subscription_id" => 1,
                "payment_id" => 5,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 1,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 2,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 3,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 4,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                "order_id" => 1,
                "payment_id" => 5,
            ]
        );

        // make sure order total paid is updated
        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $grandTotalDue,
                'total_paid' => $grandTotalDue,
                'product_due' => $productCost,
                'taxes_due' => $productTaxesDue + $shippingTaxesDue,
                'shipping_due' => $shippingCost,
                'finance_due' => $financeCosts,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => $this->brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

}