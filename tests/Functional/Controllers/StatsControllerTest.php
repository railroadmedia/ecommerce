<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

// use Railroad\Ecommerce\Repositories\OrderItemRepository;
// use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
// use Railroad\Ecommerce\Repositories\OrderRepository;
// use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
// use Railroad\Ecommerce\Repositories\PaymentRepository;
// use Railroad\Ecommerce\Repositories\ProductRepository;
// use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
// use Railroad\Ecommerce\Repositories\SubscriptionRepository;

class StatsControllerTest extends EcommerceTestCase
{

    /**
     * @var ProductRepository
     */
    // protected $productRepository;

    /**
     * @var PaymentRepository
     */
    // protected $paymentRepository;

    /**
     * @var PaymentMethodRepository
     */
    // protected $paymentMethodRepository;

    /**
     * @var OrderRepository
     */
    // protected $orderRepository;

    /**
     * @var OrderItemRepository
     */
    // protected $orderItemRepository;

    /**
     * @var OrderPaymentRepository
     */
    // protected $orderPaymentRepository;

    /**
     * @var SubscriptionRepository
     */
    // protected $subscriptionRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    // protected $subscriptionPaymentRepository;

    public function setUp()
    {
        parent::setUp();

        // $this->productRepository = $this->app->make(ProductRepository::class);
        // $this->paymentRepository = $this->app->make(PaymentRepository::class);
        // $this->paymentMethodRepository = $this->app->make(PaymentMethodRepository::class);
        // $this->orderRepository = $this->app->make(OrderRepository::class);
        // $this->orderItemRepository = $this->app->make(OrderItemRepository::class);
        // $this->orderPaymentRepository = $this->app->make(OrderPaymentRepository::class);
        // $this->subscriptionRepository = $this->app->make(SubscriptionRepository::class);
        // $this->subscriptionPaymentRepository = $this->app->make(SubscriptionPaymentRepository::class);
    }

    public function test_daily_statistics()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $userId = $this->createAndLogInNewUser();

        $userOne = $this->fakeUser();
        $userTwo = $this->fakeUser();
        $userThree = $this->fakeUser();
        $userFour = $this->fakeUser();
        $userFive = $this->fakeUser();
        $userSix = $this->fakeUser();
        $userSeven = $this->fakeUser();
        $userEight = $this->fakeUser();

        $brand = $this->faker->word;
        $smallDatetime = Carbon::now()->subDays(30)->format('Y-m-d');
        $bigDatetime = Carbon::now()->format('Y-m-d');

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productFour = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productFive = $this->fakeProduct([
            'active' => 1,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $productSix = $this->fakeProduct([
            'active' => 1,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $productSeven = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productEight = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        // orders
        $orderOneDue = $this->faker->randomFloat(2, 50, 90);
        $orderOneDate = Carbon::now()->subDays(25);

        $orderOne = $this->fakeOrder([
            'user_id' => $userOne['id'],
            'customer_id' => null,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'deleted_at' => null,
            'total_due' => $orderOneDue,
            'product_due' => $orderOneDue,
            'taxes_due' => 0,
            'shipping_due' => 0,
            'finance_due' => 0,
            'total_paid' => $orderOneDue,
            'created_at' => $orderOneDate->toDateTimeString(),
            'brand' => $brand,
        ]);

        $orderOneItemOne = $this->fakeOrderItem([
            'order_id' => $orderOne['id'],
            'product_id' => $productOne['id'],
            'quantity' => 1,
            'weight' => 0,
            'initial_price' => $productOne['price'],
            'total_discounted' => 0,
            'final_price' => $orderOneDue
        ]);

        $orderTwoDue = $this->faker->randomFloat(2, 50, 90);
        $orderTwoDate = Carbon::now()->subDays(20);

        $orderTwo = $this->fakeOrder([
            'user_id' => $userOne['id'],
            'customer_id' => null,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'deleted_at' => null,
            'total_due' => $orderTwoDue,
            'product_due' => $orderTwoDue,
            'taxes_due' => 0,
            'shipping_due' => 0,
            'finance_due' => 0,
            'total_paid' => $orderTwoDue,
            'created_at' => $orderTwoDate->toDateTimeString(),
            'brand' => $brand,
        ]);

        $orderTwoItemOne = $this->fakeOrderItem([
            'order_id' => $orderTwo['id'],
            'product_id' => $productTwo['id'],
            'quantity' => 1,
            'weight' => 0,
            'initial_price' => $productTwo['price'],
            'total_discounted' => 0,
            'final_price' => $orderTwoDue
        ]);

        $orderThreeDue = $this->faker->randomFloat(2, 50, 90);
        $orderThreeDate = Carbon::now()->subDays(20);

        $orderThree = $this->fakeOrder([
            'user_id' => $userTwo['id'],
            'customer_id' => null,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'deleted_at' => null,
            'total_due' => $orderThreeDue,
            'product_due' => $orderThreeDue,
            'taxes_due' => 0,
            'shipping_due' => 0,
            'finance_due' => 0,
            'total_paid' => $orderThreeDue,
            'created_at' => $orderThreeDate->toDateTimeString(),
            'brand' => $brand,
        ]);

        $orderThreeItemOne = $this->fakeOrderItem([
            'order_id' => $orderThree['id'],
            'product_id' => $productTwo['id'],
            'quantity' => 1,
            'weight' => 0,
            'initial_price' => $productTwo['price'],
            'total_discounted' => 0,
            'final_price' => $orderThreeDue
        ]);

        $orderFourDue = $this->faker->randomFloat(2, 50, 90);
        $orderFourDate = Carbon::now()->subDays(15);

        $orderFour = $this->fakeOrder([
            'user_id' => $userFour['id'],
            'customer_id' => null,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'deleted_at' => null,
            'total_due' => $orderFourDue,
            'product_due' => $orderFourDue,
            'taxes_due' => 0,
            'shipping_due' => 0,
            'finance_due' => 0,
            'total_paid' => $orderFourDue,
            'created_at' => $orderFourDate->toDateTimeString(),
            'brand' => $brand,
        ]);

        $orderFourItemOne = $this->fakeOrderItem([
            'order_id' => $orderFour['id'],
            'product_id' => $productThree['id'],
            'quantity' => 1,
            'weight' => 0,
            'initial_price' => $productThree['price'],
            'total_discounted' => 0,
            'final_price' => $orderFourDue
        ]);

        // payments for orders
        $creditCardOne = $this->fakeCreditCard();

        $billingAddressOne = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethodOne = $this->fakePaymentMethod([
            'method_id' => $creditCardOne['id'],
            'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_address_id' => $billingAddressOne['id']
        ]);

        $paymentOne = $this->fakePayment([
            'payment_method_id' => $paymentMethodOne['id'],
            'total_due' => $orderOneDue,
            'total_paid' => $orderOneDue,
            'total_refunded' => 0,
            'status' => Payment::STATUS_PAID,
            'conversion_rate' => 1,
            'deleted_at' => null,
            'updated_at' => null,
            'created_at' => $orderOneDate->toDateTimeString(),
        ]);

        $orderPaymentOne = $this->fakeOrderPayment([
            'order_id' => $orderOne['id'],
            'payment_id' => $paymentOne['id'],
            'created_at' => $orderOneDate->toDateTimeString(),
        ]);

        $creditCardTwo = $this->fakeCreditCard();

        $billingAddressTwo = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethodTwo = $this->fakePaymentMethod([
            'method_id' => $creditCardTwo['id'],
            'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_address_id' => $billingAddressTwo['id']
        ]);

        $paymentTwo = $this->fakePayment([
            'payment_method_id' => $paymentMethodTwo['id'],
            'total_due' => $orderTwoDue,
            'total_paid' => $orderTwoDue,
            'total_refunded' => 0,
            'status' => Payment::STATUS_PAID,
            'conversion_rate' => 1,
            'deleted_at' => null,
            'updated_at' => null,
            'created_at' => $orderTwoDate->toDateTimeString(),
        ]);

        $orderPaymentTwo = $this->fakeOrderPayment([
            'order_id' => $orderTwo['id'],
            'payment_id' => $paymentTwo['id'],
            'created_at' => $orderTwoDate->toDateTimeString(),
        ]);

        $creditCardThree = $this->fakeCreditCard();

        $billingAddressThree = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethodThree = $this->fakePaymentMethod([
            'method_id' => $creditCardThree['id'],
            'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_address_id' => $billingAddressThree['id']
        ]);

        $paymentThree = $this->fakePayment([
            'payment_method_id' => $paymentMethodThree['id'],
            'total_due' => $orderThreeDue,
            'total_paid' => $orderThreeDue,
            'total_refunded' => 0,
            'status' => Payment::STATUS_PAID,
            'conversion_rate' => 1,
            'deleted_at' => null,
            'updated_at' => null,
            'created_at' => $orderThreeDate->toDateTimeString(),
        ]);

        $orderPaymentThree = $this->fakeOrderPayment([
            'order_id' => $orderThree['id'],
            'payment_id' => $paymentThree['id'],
            'created_at' => $orderThreeDate->toDateTimeString(),
        ]);

        $creditCardFour = $this->fakeCreditCard();

        $billingAddressFour = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethodFour = $this->fakePaymentMethod([
            'method_id' => $creditCardFour['id'],
            'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_address_id' => $billingAddressFour['id']
        ]);

        $paymentFour = $this->fakePayment([
            'payment_method_id' => $paymentMethodFour['id'],
            'total_due' => $orderFourDue,
            'total_paid' => $orderFourDue,
            'total_refunded' => 0,
            'status' => Payment::STATUS_PAID,
            'conversion_rate' => 1,
            'deleted_at' => null,
            'updated_at' => null,
            'created_at' => $orderFourDate->toDateTimeString(),
        ]);

        $orderPaymentFour = $this->fakeOrderPayment([
            'order_id' => $orderFour['id'],
            'payment_id' => $paymentFour['id'],
            'created_at' => $orderFourDate->toDateTimeString(),
        ]);

        // subscriptions & payments
        $creditCardFive = $this->fakeCreditCard();

        $billingAddressFive = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethodFive = $this->fakePaymentMethod([
            'method_id' => $creditCardFive['id'],
            'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_address_id' => $billingAddressFive['id']
        ]);

        $subscriptionOneDue = $this->faker->randomFloat(2, 50, 90);
        $subscriptionOneDate = Carbon::now()->subDays(15);

        $subscriptionOne = $this->fakeSubscription([
            'product_id' => $productFour['id'],
            'payment_method_id' => $paymentMethodFive['id'],
            'user_id' => $userFive['id'],
            'paid_until' => Carbon::now()
                ->addYear(1)
                ->startOfDay()
                ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
            'created_at' => $subscriptionOneDate->toDateTimeString(),
            'brand' => $brand,
        ]);

        $paymentFive = $this->fakePayment([
            'payment_method_id' => $paymentMethodFive['id'],
            'total_due' => $subscriptionOneDue,
            'total_paid' => $subscriptionOneDue,
            'total_refunded' => 0,
            'status' => Payment::STATUS_PAID,
            'conversion_rate' => 1,
            'deleted_at' => null,
            'updated_at' => null,
            'created_at' => $subscriptionOneDate->toDateTimeString(),
        ]);

        $subscriptionOnePayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscriptionOne['id'],
            'payment_id' => $paymentFive['id'],
        ]);

        $creditCardSix = $this->fakeCreditCard();

        $billingAddressSix = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethodSix = $this->fakePaymentMethod([
            'method_id' => $creditCardSix['id'],
            'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_address_id' => $billingAddressSix['id']
        ]);

        $subscriptionTwoDue = $this->faker->randomFloat(2, 50, 90);
        $subscriptionTwoDate = Carbon::now()->subDays(10);

        $subscriptionTwo = $this->fakeSubscription([
            'product_id' => $productFive['id'],
            'payment_method_id' => $paymentMethodSix['id'],
            'user_id' => $userSix['id'],
            'paid_until' => Carbon::now()
                ->addYear(1)
                ->startOfDay()
                ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
            'created_at' => $subscriptionTwoDate->toDateTimeString(),
            'brand' => $brand,
        ]);

        $paymentSix = $this->fakePayment([
            'payment_method_id' => $paymentMethodSix['id'],
            'total_due' => $subscriptionTwoDue,
            'total_paid' => $subscriptionTwoDue,
            'total_refunded' => 0,
            'status' => Payment::STATUS_PAID,
            'conversion_rate' => 1,
            'deleted_at' => null,
            'updated_at' => null,
            'created_at' => $subscriptionTwoDate->toDateTimeString(),
        ]);

        $subscriptionTwoPayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscriptionTwo['id'],
            'payment_id' => $paymentSix['id'],
        ]);

        // failed subscription & payment
        $creditCardSeven = $this->fakeCreditCard();

        $billingAddressSeven = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethodSeven = $this->fakePaymentMethod([
            'method_id' => $creditCardSeven['id'],
            'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_address_id' => $billingAddressSeven['id']
        ]);

        $subscriptionThreeDue = $this->faker->randomFloat(2, 50, 90);
        $subscriptionThreeDate = Carbon::now()->subDays(8);

        $subscriptionThree = $this->fakeSubscription([
            'product_id' => $productSix['id'],
            'payment_method_id' => $paymentMethodSeven['id'],
            'user_id' => $userSeven['id'],
            'paid_until' => Carbon::now()
                ->addYear(1)
                ->startOfDay()
                ->toDateTimeString(),
            'is_active' => 0,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
            'created_at' => $subscriptionThreeDate->toDateTimeString(),
            'brand' => $brand,
        ]);

        $paymentSeven = $this->fakePayment([
            'payment_method_id' => $paymentMethodSeven['id'],
            'total_due' => $subscriptionThreeDue,
            'total_paid' => $subscriptionThreeDue,
            'total_refunded' => 0,
            'status' => Payment::STATUS_FAILED,
            'conversion_rate' => 1,
            'deleted_at' => null,
            'updated_at' => null,
            'created_at' => $subscriptionThreeDate->toDateTimeString(),
        ]);

        $subscriptionTwoPayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscriptionThree['id'],
            'payment_id' => $paymentSeven['id'],
        ]);

        // failed order & payment
        $orderFiveDue = $this->faker->randomFloat(2, 50, 90);
        $orderFiveDate = Carbon::now()->subDays(5);

        $orderFive = $this->fakeOrder([
            'user_id' => $userOne['id'],
            'customer_id' => null,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'deleted_at' => null,
            'total_due' => $orderFiveDue,
            'product_due' => $orderFiveDue,
            'taxes_due' => 0,
            'shipping_due' => 0,
            'finance_due' => 0,
            'total_paid' => $orderFiveDue,
            'created_at' => $orderFiveDate->toDateTimeString(),
            'brand' => $brand,
        ]);

        $orderFiveItemOne = $this->fakeOrderItem([
            'order_id' => $orderFive['id'],
            'product_id' => $productSeven['id'],
            'quantity' => 1,
            'weight' => 0,
            'initial_price' => $productSeven['price'],
            'total_discounted' => 0,
            'final_price' => $orderFiveDue
        ]);

        $creditCardEight = $this->fakeCreditCard();

        $billingAddressEight = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethodEight = $this->fakePaymentMethod([
            'method_id' => $creditCardEight['id'],
            'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'billing_address_id' => $billingAddressEight['id']
        ]);

        $paymentEight = $this->fakePayment([
            'payment_method_id' => $paymentMethodEight['id'],
            'total_due' => $orderFiveDue,
            'total_paid' => $orderFiveDue,
            'total_refunded' => 0,
            'status' => Payment::STATUS_FAILED,
            'conversion_rate' => 1,
            'deleted_at' => null,
            'updated_at' => null,
            'created_at' => $orderFiveDate->toDateTimeString(),
        ]);

        $orderPaymentFive = $this->fakeOrderPayment([
            'order_id' => $orderFive['id'],
            'payment_id' => $paymentEight['id'],
            'created_at' => $orderFiveDate->toDateTimeString(),
        ]);

        // refund
        $refundDue = $this->faker->randomFloat(2, 50, 90);
        $refundDate = Carbon::now()->subDays(5);

        $creditCardNine = $this->fakeCreditCard();

        $billingAddressNine = $this->fakeAddress(
            [
                'type' => Address::BILLING_ADDRESS_TYPE
            ]
        );

        $paymentMethodNine = $this->fakePaymentMethod(
            [
                'method_id' => $creditCardNine['id'],
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'billing_address_id' => $billingAddressNine['id']
            ]
        );

        $paymentNine = $this->fakePayment(
            [
                'payment_method_id' => $paymentMethodNine['id'],
                'total_due' => $refundDue,
                'total_paid' => $refundDue,
                'total_refunded' => $refundDue,
                'deleted_at' => null,
                'updated_at' => null
            ]
        );

        $refund = $this->fakeRefund(
            [
                'payment_amount' => $refundDue,
                'refunded_amount' => $refundDue,
                'payment_id' => $paymentNine['id'],
                'created_at' => $refundDate->toDateTimeString(),
            ]
        );

        $orderSixDate = Carbon::now()->subMonths(2); // outside of current stats period, but refund date is included

        $orderSix = $this->fakeOrder([
            'user_id' => $userEight['id'],
            'customer_id' => null,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'deleted_at' => null,
            'total_due' => $refundDue,
            'product_due' => $refundDue,
            'taxes_due' => 0,
            'shipping_due' => 0,
            'finance_due' => 0,
            'total_paid' => $refundDue,
            'created_at' => $orderSixDate->toDateTimeString(),
            'brand' => $brand,
        ]);

        $orderSixItemOne = $this->fakeOrderItem([
            'order_id' => $orderSix['id'],
            'product_id' => $productEight['id'],
            'quantity' => 1,
            'weight' => 0,
            'initial_price' => $productEight['price'],
            'total_discounted' => 0,
            'final_price' => $refundDue
        ]);

        $orderPaymentSix = $this->fakeOrderPayment([
            'order_id' => $orderSix['id'],
            'payment_id' => $paymentNine['id'],
            'created_at' => $orderSixDate->toDateTimeString(),
        ]);

        $response = $this->call(
            'GET',
            '/daily-statistics',
            [
                'small_date_time' => $smallDatetime,
                'big_date_time' => $bigDatetime,
                'brand' => $brand
            ]
        );

        $expected = [
            'data' => [
                [
                    'type' => 'dailyStatistic',
                    'id' => $orderOneDate->format('Y-m-d'),
                    'attributes' => [
                        'total_sales' => $orderOneDue,
                        'total_refunded' => 0,
                        'total_number_of_orders_placed' => 1,
                        'total_number_of_successful_subscription_renewal_payments' => 0,
                        'total_number_of_failed_subscription_renewal_payments' => 0,
                        'day' => $orderOneDate->format('Y-m-d'),
                    ],
                    'relationships' => [
                        'productStatistic' => [
                            'data' => [
                                [
                                    'type' => 'productStatistic',
                                    'id' => $orderOneDate->format('Y-m-d') . ':' . $productOne['id'],
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'dailyStatistic',
                    'id' => $orderTwoDate->format('Y-m-d'),
                    'attributes' => [
                        'total_sales' => round($orderTwoDue + $orderThreeDue, 2),
                        'total_refunded' => 0,
                        'total_number_of_orders_placed' => 2,
                        'total_number_of_successful_subscription_renewal_payments' => 0,
                        'total_number_of_failed_subscription_renewal_payments' => 0,
                        'day' => $orderTwoDate->format('Y-m-d'),
                    ],
                    'relationships' => [
                        'productStatistic' => [
                            'data' => [
                                [
                                    'type' => 'productStatistic',
                                    'id' => $orderTwoDate->format('Y-m-d') . ':' . $productTwo['id'],
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'dailyStatistic',
                    'id' => $orderFourDate->format('Y-m-d'),
                    'attributes' => [
                        'total_sales' => round($orderFourDue + $subscriptionOneDue, 2),
                        'total_refunded' => 0,
                        'total_number_of_orders_placed' => 1,
                        'total_number_of_successful_subscription_renewal_payments' => 1,
                        'total_number_of_failed_subscription_renewal_payments' => 0,
                        'day' => $orderFourDate->format('Y-m-d'),
                    ],
                    'relationships' => [
                        'productStatistic' => [
                            'data' => [
                                [
                                    'type' => 'productStatistic',
                                    'id' => $orderFourDate->format('Y-m-d') . ':' . $productThree['id'],
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'dailyStatistic',
                    'id' => $subscriptionTwoDate->format('Y-m-d'),
                    'attributes' => [
                        'total_sales' => $subscriptionTwoDue,
                        'total_refunded' => 0,
                        'total_number_of_orders_placed' => 0,
                        'total_number_of_successful_subscription_renewal_payments' => 1,
                        'total_number_of_failed_subscription_renewal_payments' => 0,
                        'day' => $subscriptionTwoDate->format('Y-m-d'),
                    ],
                ],
                [
                    'type' => 'dailyStatistic',
                    'id' => $subscriptionThreeDate->format('Y-m-d'),
                    'attributes' => [
                        'total_sales' => 0,
                        'total_refunded' => 0,
                        'total_number_of_orders_placed' => 0,
                        'total_number_of_successful_subscription_renewal_payments' => 0,
                        'total_number_of_failed_subscription_renewal_payments' => 1,
                        'day' => $subscriptionThreeDate->format('Y-m-d'),
                    ],
                ],
                [
                    'type' => 'dailyStatistic',
                    'id' => $refundDate->format('Y-m-d'),
                    'attributes' => [
                        'total_sales' => 0,
                        'total_refunded' => $refundDue,
                        'total_number_of_orders_placed' => 0,
                        'total_number_of_successful_subscription_renewal_payments' => 0,
                        'total_number_of_failed_subscription_renewal_payments' => 0,
                        'day' => $refundDate->format('Y-m-d'),
                    ],
                ],
            ],
            'included' => [
                [
                    'type' => 'productStatistic',
                    'id' => $orderOneDate->format('Y-m-d') . ':' . $productOne['id'],
                    'attributes' =>  [
                        'sku' => $productOne['sku'],
                        'total_quantity_sold' => 1,
                        'total_sales' => $orderOneDue,
                    ],
                ],
                [
                    'type' => 'productStatistic',
                    'id' => $orderTwoDate->format('Y-m-d') . ':' . $productTwo['id'],
                    'attributes' =>  [
                        'sku' => $productTwo['sku'],
                        'total_quantity_sold' => 2,
                        'total_sales' => round($orderTwoDue + $orderThreeDue, 2),
                    ],
                ],
                [
                    'type' => 'productStatistic',
                    'id' => $orderFourDate->format('Y-m-d') . ':' . $productThree['id'],
                    'attributes' =>  [
                        'sku' => $productThree['sku'],
                        'total_quantity_sold' => 1,
                        'total_sales' => $orderFourDue,
                    ],
                ],
            ]
        ];

        $this->assertEquals(
            $expected,
            $response->decodeResponseJson()
        );
    }

    /*
    public function test_stats_product_no_payments()
    {
        $product = $this->faker->product();
        $this->productRepository->create($product);
        $results = $this->call(
            'GET',
            '/stats/products/',
            [
                'start-date' => '2018-07-01',
                'end-date' => '2018-07-31',
            ]
        );

        $this->assertEquals(2, count($results->decodeResponseJson('data')[0]['productStats']));
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalPaid']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalRefunded']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalShipping']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalFinance']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalTax']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalNet']);
    }

    public function test_stats_products_with_payments()
    {

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 10,
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 5,
                ]
            )
        );
        $order = $this->orderRepository->create(
            $this->faker->order(
                [
                    'due' => ($product->price + $product2->price),
                    'tax' => 10,
                    'shipping_costs' => 0,
                    'paid' => ($product->price + $product2->price),
                ]
            )
        );
        $orderItem1 = $this->orderItemRepository->create(
            $this->faker->orderItem(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'initial_price' => $product->price,
                    'tax' => 6,
                    'shipping_costs' => 0,
                    'total_price' => $product->price,
                ]
            )
        );
        $orderItem2 = $this->orderItemRepository->create(
            $this->faker->orderItem(
                [
                    'order_id' => $order->id,
                    'product_id' => $product2->id,
                    'quantity' => 1,
                    'initial_price' => $product2->price,
                    'tax' => 3,
                    'shipping_costs' => 0,
                    'total_price' => $product2->price,
                ]
            )
        );

        $payment = $this->paymentRepository->create(
            $this->faker->payment(
                [
                    'due' => $order->due,
                    'paid' => $order->due,
                    'refunded' => 0,
                    'type' => 'order',
                    'status' => 1,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );
        $this->orderPaymentRepository->create(
            [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
        $results = $this->call(
            'GET',
            '/stats/products/'
        );

        $this->assertEquals(3, count($results->decodeResponseJson('data')[0]['productStats']));
        $this->assertEquals(
            $product->price,
            $results->decodeResponseJson('data')[0]['productStats'][$product->id]['paid']
        );
        $this->assertEquals(
            6.6666666666667,
            $results->decodeResponseJson('data')[0]['productStats'][$product->id]['tax']
        );
        $this->assertEquals(($product->price + $product2->price), $results->decodeResponseJson('data')[0]['totalPaid']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalRefunded']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalShipping']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalFinance']);
        $this->assertEquals($order->tax, $results->decodeResponseJson('data')[0]['totalTax']);
        $this->assertEquals(($product->price + $product2->price), $results->decodeResponseJson('data')[0]['totalNet']);
    }

    public function test_order_stats_no_results()
    {
        $results = $this->call(
            'GET',
            '/stats/orders/',
            [
                'start-date' => '2017-12-01 00:00:00',
                'end-date' => '2018-02-10 23:59:59',
            ]
        );

        $this->assertEquals([], $results->decodeResponseJson('data')[0]['rows']);
        $this->assertEquals(
            [
                'net paid' => 0,
                "shipping paid" => 0,
                "finance paid" => 0,
                "tax paid" => 0,
                "total paid" => 0,
            ],
            $results->decodeResponseJson('data')[0]['totalRows']
        );
    }

    public function test_order_stats()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 10,
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 5,
                ]
            )
        );
        $order = $this->orderRepository->create(
            $this->faker->order(
                [
                    'due' => ($product->price + $product2->price),
                    'tax' => 9,
                    'shipping_costs' => 0,
                    'paid' => ($product->price + $product2->price),
                ]
            )
        );

        $orderItem1 = $this->orderItemRepository->create(
            $this->faker->orderItem(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'initial_price' => $product->price,
                    'tax' => 6,
                    'shipping_costs' => 0,
                    'total_price' => $product->price,
                ]
            )
        );
        $orderItem2 = $this->orderItemRepository->create(
            $this->faker->orderItem(
                [
                    'order_id' => $order->id,
                    'product_id' => $product2->id,
                    'quantity' => 1,
                    'initial_price' => $product2->price,
                    'tax' => 3,
                    'shipping_costs' => 0,
                    'total_price' => $product2->price,
                ]
            )
        );
        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_id' => rand(),
                    'method_type' => 'credit-card',
                ]
            )
        );

        $payment = $this->paymentRepository->create(
            $this->faker->payment(
                [
                    'due' => $order->due,
                    'paid' => $order->due,
                    'payment_method_id' => $paymentMethod->id,
                    'refunded' => 0,
                    'type' => 'order',
                    'status' => 1,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );

        $this->orderPaymentRepository->create(
            [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
        $results = $this->call(
            'GET',
            '/stats/orders/'
        );

        $this->assertEquals(1, count($results->decodeResponseJson('data')[0]['rows']));
        $this->assertEquals(
            [
                'email' => 'unknown',
                'address' => '',
                'name' => '',
                'products' => $product['name'] .
                    ' - ' .
                    $orderItem1['quantity'] .
                    '<br>' .
                    $product2['name'] .
                    ' - ' .
                    $orderItem2['quantity'] .
                    '<br>',
                'net paid' => $product->price + $product2->price - $order->tax,
                'shipping paid' => '0',
                'tax paid' => $order->tax,
                'finance paid' => 0,
                'total paid' => $product->price + $product2->price,

            ],
            $results->decodeResponseJson('data')[0]['rows'][0]
        );
        $this->assertEquals(
            [
                'net paid' => $product->price + $product2->price - $order->tax,
                'shipping paid' => '0',
                'tax paid' => $order->tax,
                'finance paid' => 0,
                'total paid' => $product->price + $product2->price,
            ],
            $results->decodeResponseJson('data')[0]['totalRows']
        );
    }

    public function test_order_stats_order_and_renewal()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 10,
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 5,
                ]
            )
        );
        $order = $this->orderRepository->create(
            $this->faker->order(
                [
                    'due' => ($product->price + $product2->price),
                    'tax' => 9,
                    'shipping_costs' => 0,
                    'paid' => ($product->price + $product2->price) - 1,
                ]
            )
        );

        $orderItem1 = $this->orderItemRepository->create(
            $this->faker->orderItem(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'initial_price' => $product->price,
                    'tax' => 6,
                    'shipping_costs' => 0,
                    'total_price' => $product->price,
                ]
            )
        );
        $orderItem2 = $this->orderItemRepository->create(
            $this->faker->orderItem(
                [
                    'order_id' => $order->id,
                    'product_id' => $product2->id,
                    'quantity' => 1,
                    'initial_price' => $product2->price,
                    'tax' => 3,
                    'shipping_costs' => 0,
                    'total_price' => $product2->price,
                ]
            )
        );
        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_id' => rand(),
                    'method_type' => 'credit-card',
                ]
            )
        );

        $payment = $this->paymentRepository->create(
            $this->faker->payment(
                [
                    'due' => $order->due,
                    'paid' => $order->due,
                    'payment_method_id' => $paymentMethod->id,
                    'refunded' => 0,
                    'type' => 'order',
                    'status' => 1,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );

        $this->orderPaymentRepository->create(
            [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $subscription = $this->subscriptionRepository->create(
            $this->faker->subscription(
                [
                    'product_id' => $product->id,
                    'order_id' => null,
                    'total_price_per_payment' => $product->price,
                    'tax_per_payment' => 0,
                    'shipping_per_payment' => 0,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );

        $payment2 = $this->paymentRepository->create(
            $this->faker->payment(
                [
                    'due' => $subscription->total_price_per_payment,
                    'paid' => $subscription->total_price_per_payment,
                    'payment_method_id' => $paymentMethod->id,
                    'refunded' => 0,
                    'type' => 'renewal',
                    'status' => 1,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );
        $subscriptionPayment = $this->subscriptionPaymentRepository->create(
            [
                'subscription_id' => $subscription->id,
                'payment_id' => $payment2->id,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $orderForPaymentPlan = $this->orderRepository->create(
            $this->faker->order(
                [
                    'due' => $product2->price / 6,
                    'tax' => 0,
                    'shipping_costs' => 0,
                    'paid' => $product2->price / 6,
                ]
            )
        );

        $orderItemPaymentPlan = $this->orderItemRepository->create(
            $this->faker->orderItem(
                [
                    'order_id' => $orderForPaymentPlan->id,
                    'product_id' => $product2->id,
                    'quantity' => 1,
                    'initial_price' => $product2->price,
                    'tax' => 0,
                    'shipping_costs' => 0,
                    'total_price' => $product2->price / 6,
                ]
            )
        );
        $paymentPlan = $this->subscriptionRepository->create(
            $this->faker->subscription(
                [
                    'product_id' => null,
                    'order_id' => $orderForPaymentPlan->id,
                    'type' => 'payment plan',
                    'total_price_per_payment' => $product2->price / 6,
                    'tax_per_payment' => 0,
                    'shipping_per_payment' => 0,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );

        $payment2 = $this->paymentRepository->create(
            $this->faker->payment(
                [
                    'due' => $subscription->total_price_per_payment,
                    'paid' => $subscription->total_price_per_payment,
                    'payment_method_id' => $paymentMethod->id,
                    'refunded' => 0,
                    'type' => 'subscription',
                    'status' => 1,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );
        $subscriptionPayment = $this->subscriptionPaymentRepository->create(
            [
                'subscription_id' => $subscription->id,
                'payment_id' => $payment2->id,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $payment3 = $this->paymentRepository->create(
            $this->faker->payment(
                [
                    'due' => $paymentPlan->total_price_per_payment,
                    'paid' => $paymentPlan->total_price_per_payment,
                    'payment_method_id' => $paymentMethod->id,
                    'refunded' => 0,
                    'type' => 'renewal',
                    'status' => 1,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );
        $subscriptionPayment = $this->subscriptionPaymentRepository->create(
            [
                'subscription_id' => $paymentPlan->id,
                'payment_id' => $payment3->id,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $results = $this->call(
            'GET',
            '/stats/orders/'
        );

        $this->assertEquals(3, count($results->decodeResponseJson('data')[0]['rows']));
        $this->assertEquals(
            [
                [
                    'email' => 'unknown',
                    'address' => '',
                    'name' => '',
                    'products' => $product['name'] .
                        ' - ' .
                        $orderItem1['quantity'] .
                        '<br>' .
                        $product2['name'] .
                        ' - ' .
                        $orderItem2['quantity'] .
                        '<br>',
                    'net paid' => $product->price + $product2->price - $order->tax - 1,
                    'shipping paid' => '0',
                    'tax paid' => $order->tax,
                    'finance paid' => 1,
                    'total paid' => $product->price + $product2->price,

                ],
                [
                    'email' => 'unknown',
                    'address' => '',
                    'name' => '',
                    'products' => $product['name'],
                    'net paid' => $product->price,
                    'shipping paid' => $subscription->shipping_per_payment,
                    'tax paid' => $subscription->tax_per_payment,
                    'finance paid' => 0,
                    'total paid' => $product->price,

                ],
                [
                    'email' => 'unknown',
                    'address' => '',
                    'name' => '',
                    'products' => $product2['name'] . ' - ' . $orderItemPaymentPlan->quantity . '<br>',
                    'net paid' => $product2->price / 6,
                    'shipping paid' => $orderForPaymentPlan->shipping_costs,
                    'tax paid' => $orderForPaymentPlan->tax,
                    'finance paid' => 0,
                    'total paid' => $product2->price / 6,

                ],
            ],
            $results->decodeResponseJson('data')[0]['rows']
        );

        $this->assertEquals(
            [
                'net paid' => $product->price +
                    $product2->price -
                    $order->tax +
                    $subscription->total_price_per_payment +
                    $paymentPlan->total_price_per_payment -
                    1,
                'shipping paid' => $order->shipping_costs + $subscription->shipping_per_payment,
                'tax paid' => $order->tax + $subscription->tax_per_payment,
                'finance paid' => 1,
                'total paid' => $product->price +
                    $product2->price +
                    $subscription->total_price_per_payment +
                    $paymentPlan->total_price_per_payment,
            ],
            $results->decodeResponseJson('data')[0]['totalRows']
        );
    }
    */
}
