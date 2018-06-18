<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;

use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
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

    public function setUp()
    {
        parent::setUp();

        $this->paymentRepository           = $this->app->make(PaymentRepository::class);
        $this->paymentMethodRepository     = $this->app->make(PaymentMethodRepository::class);
        $this->creditCardRepository        = $this->app->make(CreditCardRepository::class);
        $this->userPaymentMethodRepository = $this->app->make(UserPaymentMethodsRepository::class);
    }

    public function test_store_validation()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $results = $this->call('PUT', '/refund', [
            'payment_id'    => rand(),
            'note'          => '',
            'refund_amount' => rand()
        ]);

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
                [
                    "source" => "payment_id",
                    "detail" => "The selected payment id is invalid.",
                ]
            ]
            , $results->decodeResponseJson()['errors']);
        $this->assertArraySubset([], $results->decodeResponseJson()['results']);
    }

    public function test_user_create_own_refund()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow');
        $this->stripeExternalHelperMock->method('createRefund')->willReturn(1);

        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod  = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => 'credit-card',
            'method_id'   => $creditCard['id']
        ]));
        $userPayment    = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id'],
        ]));
        $payment        = $this->paymentRepository->create($this->faker->payment([
            'payment_method_id' => $paymentMethod['id'],
            'external_id'       => 'ch_1CQFAJE2yPYKc9YRFZUa5ACI'
        ]));
        $refundAmount   = $this->faker->numberBetween(0, 100);

        $results = $this->call('PUT', '/refund', [
            'payment_id'    => $payment['id'],
            'refund_amount' => $refundAmount,
            'gateway-name' => 'drumeo'
        ]);

        //assert refund data subset of results
        $this->assertEquals(200, $results->getStatusCode());
        $this->assertArraySubset([
            'payment_id'        => $payment['id'],
            'payment_amount'    => $payment['due'],
            'refunded_amount'   => $refundAmount,
            'note'              => '',
            'external_provider' => $payment['external_provider'],
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $results->decodeResponseJson()['results']);

        //assert refund raw saved in db
        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id'        => $payment['id'],
                'payment_amount'    => $payment['due'],
                'refunded_amount'   => $refundAmount,
                'note'              => null,
                'external_provider' => $payment['external_provider'],
                'created_on'        => Carbon::now()->toDateTimeString(),
                'updated_on'        => null
            ]
        );
    }

    public function test_user_can_not_create_other_refund()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow');

        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod  = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => 'credit-card',
            'method_id'   => $creditCard['id']
        ]));
        $userPayment    = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => rand(),
            'payment_method_id' => $paymentMethod['id'],
        ]));
        $payment        = $this->paymentRepository->create($this->faker->payment([
            'payment_method_id' => $paymentMethod['id'],
            'external_id'       => 'ch_1CQFAJE2yPYKc9YRFZUa5ACI'
        ]));
        $refundAmount   = $this->faker->numberBetween(0, 100);

        $results = $this->call('PUT', '/refund', [
            'payment_id'    => $payment['id'],
            'refund_amount' => $refundAmount
        ]);

        $this->assertEquals(403, $results->getStatusCode());

        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }
}
