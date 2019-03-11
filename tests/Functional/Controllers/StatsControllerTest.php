<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
// use Railroad\Ecommerce\Repositories\OrderItemRepository;
// use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
// use Railroad\Ecommerce\Repositories\OrderRepository;
// use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
// use Railroad\Ecommerce\Repositories\PaymentRepository;
// use Railroad\Ecommerce\Repositories\ProductRepository;
// use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
// use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class StatsControllerTest extends EcommerceTestCase
{

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var PaymentMethodRepository
     */
    protected $paymentMethodRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var OrderPaymentRepository
     */
    protected $orderPaymentRepository;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

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

    public function test_true()
    {
        // test temp disabled, until controller is migrated
        // to be removed/updated

        $this->assertTrue(true);
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
