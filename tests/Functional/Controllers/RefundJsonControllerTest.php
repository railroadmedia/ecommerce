<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;

use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class RefundJsonControllerTest extends EcommerceTestCase
{

    CONST VALID_VISA_CARD_NUM = '4242424242424242';

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentMethodRepository
     */
    protected $paymentMethodRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\CreditCardRepository
     */
    protected $creditCardRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository
     */
    protected $userPaymentMethodRepository;

    /**
     * @var OrderPaymentRepository
     */
    protected $orderPaymentRepository;

    /**
     * @var OrderItemFulfillmentRepository
     */
    protected $orderItemFulfillmentRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;


    public function setUp()
    {
        parent::setUp();

        $this->paymentRepository = $this->app->make(PaymentRepository::class);
        $this->paymentMethodRepository = $this->app->make(PaymentMethodRepository::class);
        $this->creditCardRepository = $this->app->make(CreditCardRepository::class);
        $this->userPaymentMethodRepository = $this->app->make(UserPaymentMethodsRepository::class);
        $this->orderPaymentRepository = $this->app->make(OrderPaymentRepository::class);
        $this->orderItemFulfillmentRepository = $this->app->make(OrderItemFulfillmentRepository::class);
        $this->orderItemRepository = $this->app->make(OrderItemRepository::class);
        $this->orderRepository = $this->app->make(OrderRepository::class);
        $this->productRepository = $this->app->make(ProductRepository::class);
    }

    public function test_store_validation()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $results = $this->call(
            'PUT',
            '/refund',
            [
                'payment_id' => rand(),
                'note' => '',
                'refund_amount' => rand(),
                'gateway_name' => $this->faker->word,
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(
            [
                [
                    "source" => "payment_id",
                    "detail" => "The selected payment id is invalid.",
                ],
            ],
            $results->decodeResponseJson('meta')['errors']
        );
        $this->assertArraySubset([], $results->decodeResponseJson('data'));
    }

    public function test_user_create_own_refund()
    {
        $userId = $this->createAndLogInNewUser();
        $refund = new \stdClass();
        $refund->id = 1;
        $this->stripeExternalHelperMock->method('createRefund')
            ->willReturn($refund);

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => 'credit-card',
                    'method_id' => $creditCard['id'],
                ]
            )
        );
        $userPayment = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );
        $payment = $this->paymentRepository->create(
            $this->faker->payment(
                [
                    'due' => 100,
                    'payment_method_id' => $paymentMethod['id'],
                    'external_id' => 'ch_1CQFAJE2yPYKc9YRFZUa5ACI',
                ]
            )
        );
        $orderId = $this->orderRepository->create($this->faker->order(['user_id' => $userId]));
        for ($i = 0; $i< 3; $i++){
            $product[$i] = $this->productRepository->create($this->faker->product());
            $orderItem[$i] = $this->orderItemRepository->create($this->faker->orderItem([
                'order_id' => $orderId['id'],
                'product_id' => $product[$i]['id']

            ]));

        }

        $orderPayment = $this->orderPaymentRepository->create([
            'order_id' => $orderId['id'],
            'payment_id' => $payment['id'],
            'created_on' => Carbon::now()->toDateTimeString()
        ]);
        $orderItemfulfillemt = $this->orderItemFulfillmentRepository->create(
            $this->faker->orderItemFulfillment([
                'order_id' => $orderId['id']
            ])
        );
        $refundAmount = 100;

        $this->permissionServiceMock->method('canOrThrow');
        $results = $this->call(
            'PUT',
            '/refund',
            [
                'payment_id' => $payment['id'],
                'refund_amount' => $refundAmount,
                'gateway_name' => 'drumeo',
            ]
        );

        //assert refund data subset of results
        $this->assertEquals(200, $results->getStatusCode());
        $this->assertArraySubset(
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['due'],
                'refunded_amount' => $refundAmount,
                'note' => '',
                'external_provider' => $payment['external_provider'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
                'updated_on' => null,
            ],
            $results->decodeResponseJson()['data'][0]
        );

        //assert refund raw saved in db
        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['due'],
                'refunded_amount' => $refundAmount,
                'note' => null,
                'external_provider' => $payment['external_provider'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
                'updated_on' => null,
            ]
        );

        //assert refund value saved in payment table
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'id' => $payment['id'],
                'refunded' => $payment['refunded'] + $refundAmount,
            ]
        );
    }

    public function test_refund_order_and_cancel_fulfilment()
    {
        $refund = new \stdClass();
        $refund->id = 1;
        $this->stripeExternalHelperMock->method('createRefund')
            ->willReturn($refund);

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => 'credit-card',
                    'method_id' => $creditCard['id'],
                ]
            )
        );
        $userPayment = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => rand(),
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );
        $payment = $this->paymentRepository->create(
            $this->faker->payment(
                [
                    'payment_method_id' => $paymentMethod['id'],
                    'external_id' => 'ch_1CQFAJE2yPYKc9YRFZUa5ACI',
                ]
            )
        );
        $order = $this->orderRepository->create($this->faker->order());
        $orderPayment = $this->orderPaymentRepository->create([
            'order_id' => $order['id'],
            'payment_id' => $payment['id'],
            'created_on' => Carbon::now()->toDateTimeString()
        ]);
        $orderItemfulfillemt = $this->orderItemFulfillmentRepository->create(
            $this->faker->orderItemFulfillment([
                'order_id' => $order['id'],
                'status' => ConfigService::$fulfillmentStatusPending
            ])
        );
        $refundAmount = $this->faker->numberBetween(0, 100);

        $this->permissionServiceMock->method('canOrThrow');
        $results = $this->call(
            'PUT',
            '/refund',
            [
                'payment_id' => $payment['id'],
                'refund_amount' => $refundAmount,
                'gateway_name' => 'drumeo',
            ]
        );

        //assert refund data subset of results
        $this->assertEquals(200, $results->getStatusCode());

        //assert refund raw saved in db
        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['due'],
                'refunded_amount' => $refundAmount,
                'note' => null,
                'external_provider' => $payment['external_provider'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
                'updated_on' => null,
            ]
        );

        //assert refund value saved in payment table
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'id' => $payment['id'],
                'refunded' => $payment['refunded'] + $refundAmount,
            ]
        );

        //assert shipping fulfillment deleted
        $this->assertDatabaseMissing(
            ConfigService::$tableOrderItemFulfillment,
            [
                'order_id' => $order['id']
            ]
        );
    }

    public function test_refund_order_shipped()
    {
        $refund = new \stdClass();
        $refund->id = 1;
        $this->stripeExternalHelperMock->method('createRefund')
            ->willReturn($refund);

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => 'credit-card',
                    'method_id' => $creditCard['id'],
                ]
            )
        );
        $userPayment = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => rand(),
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );
        $payment = $this->paymentRepository->create(
            $this->faker->payment(
                [
                    'payment_method_id' => $paymentMethod['id'],
                    'external_id' => 'ch_1CQFAJE2yPYKc9YRFZUa5ACI',
                ]
            )
        );
        $orderId = $this->faker->numberBetween();
        $orderPayment = $this->orderPaymentRepository->create([
            'order_id' => $orderId,
            'payment_id' => $payment['id'],
            'created_on' => Carbon::now()->toDateTimeString()
        ]);
        $orderItemfulfillemt = $this->orderItemFulfillmentRepository->create(
            $this->faker->orderItemFulfillment([
                'order_id' => $orderId,
                'status' => ConfigService::$fulfillmentStatusFulfilled,
                'fulfilled_on' => Carbon::now()->toDateTimeString()
            ])
        );
        $refundAmount = $this->faker->numberBetween(0, 100);

        $this->permissionServiceMock->method('canOrThrow');
        $results = $this->call(
            'PUT',
            '/refund',
            [
                'payment_id' => $payment['id'],
                'refund_amount' => $refundAmount,
                'gateway_name' => 'drumeo',
            ]
        );

        //assert refund data subset of results
        $this->assertEquals(200, $results->getStatusCode());

        //assert refund raw saved in db
        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['due'],
                'refunded_amount' => $refundAmount,
                'note' => null,
                'external_provider' => $payment['external_provider'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
                'updated_on' => null,
            ]
        );

        //assert refund value saved in payment table
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'id' => $payment['id'],
                'refunded' => $payment['refunded'] + $refundAmount,
            ]
        );

        //assert shipping fulfillment still exists in the database
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'order_id' => $orderId
            ]
        );
    }
}
