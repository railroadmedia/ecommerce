<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
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

    public function setUp()
    {
        parent::setUp();

        $this->productRepository = $this->app->make(ProductRepository::class);
        $this->paymentRepository = $this->app->make(PaymentRepository::class);
        $this->orderRepository = $this->app->make(OrderRepository::class);
        $this->orderItemRepository = $this->app->make(OrderItemRepository::class);
        $this->orderPaymentRepository = $this->app->make(OrderPaymentRepository::class);
    }

    public function testStatsProduct_no_payments()
    {
        $product = $this->faker->product();
        $this->productRepository->create($product);
        $results = $this->call(
            'GET',
            '/stats/products/'
        );

        $this->assertEquals(2, count($results->decodeResponseJson('data')[0]['productStats']));
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalPaid']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalRefunded']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalShipping']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalFinance']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalTax']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalNet']);
    }

    public function testStatsProducts_with_payments()
    {

        $product = $this->productRepository->create($this->faker->product([
            'price' => 10
        ]));
        $product2 = $this->productRepository->create($this->faker->product([
            'price' => 5
        ]));
        $order = $this->orderRepository->create($this->faker->order([
            'due' => ($product->price + $product2->price),
            'tax' => 10,
            'shipping_costs' => 0,
            'paid' => ($product->price + $product2->price)
        ]));
        $orderItem1 =
            $this->orderItemRepository->create(
                $this->faker->orderItem(
                    [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'initial_price' => $product->price,
                        'tax' => 6,
                        'shipping_costs' => 0,
                        'total_price' => $product->price
                    ]
                )
            );
        $orderItem2 =
            $this->orderItemRepository->create(
                $this->faker->orderItem(
                    [
                        'order_id' => $order->id,
                        'product_id' => $product2->id,
                        'quantity' => 1,
                        'initial_price' => $product2->price,
                        'tax' => 3,
                        'shipping_costs' => 0,
                        'total_price' => $product2->price
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
        $this->assertEquals($product->price, $results->decodeResponseJson('data')[0]['productStats'][$product->id]['paid']);
        $this->assertEquals(6.6666666666667, $results->decodeResponseJson('data')[0]['productStats'][$product->id]['tax']);
        $this->assertEquals(($product->price + $product2->price), $results->decodeResponseJson('data')[0]['totalPaid']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalRefunded']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalShipping']);
        $this->assertEquals(0, $results->decodeResponseJson('data')[0]['totalFinance']);
        $this->assertEquals($order->tax, $results->decodeResponseJson('data')[0]['totalTax']);
        $this->assertEquals(($product->price + $product2->price), $results->decodeResponseJson('data')[0]['totalNet']);
    }
}
