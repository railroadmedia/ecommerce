<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\PaymentFactory;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class RefundJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * @var PaymentGatewayFactory
     */
    private $paymentGatewayFactory;

    CONST VALID_VISA_CARD_NUM = '4242424242424242';

    /**
     * @var PaymentMethodFactory
     */
    protected $paymentMethodFactory;

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

    protected $paymentGatewayRepository;

    public function setUp()
    {
        parent::setUp();
        $this->paymentMethodFactory    = $this->app->make(PaymentMethodFactory::class);
        $this->paymentFactory          = $this->app->make(PaymentFactory::class);
        $this->paymentRepository       = $this->app->make(PaymentRepository::class);
        $this->paymentMethodRepository = $this->app->make(PaymentMethodRepository::class);
        $this->creditCardRepository    = $this->app->make(CreditCardRepository::class);
        $this->paymentGatewayRepository = $this->app->make(PaymentGatewayRepository::class);
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


    public function test_user_create_refund()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));
        $creditCard    = $this->creditCardRepository->create($this->faker->creditCard(['payment_gateway_id' => $paymentGateway['id']]));
        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => 'credit card',
            'method_id' => $creditCard['id']
        ]));
        $payment       = $this->paymentRepository->create($this->faker->payment([
            'payment_method_id' => $paymentMethod['id'],
            'external_id'       => 'ch_1CQFAJE2yPYKc9YRFZUa5ACI'
        ]));

        $refundAmount = $this->faker->numberBetween(0, 100);

        $results = $this->call('PUT', '/refund', [
            'payment_id'    => $payment['id'],
            'note'          => '',
            'refund_amount' => $refundAmount
        ]);

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
    }

    public function _test_user_can_not_create_other_refund()
    {
        $this->createAndLogInNewUser();
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway     = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $cardExpirationDate->format('Y'),
            $cardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->faker->word,
            rand(),
            $this->faker->currencyCode,
            null,
            rand());

        $payment = $this->paymentFactory->store($this->faker->numberBetween(0, 9000),
            0,
            0,
            null,
            null,
            true,
            '',
            $paymentMethod['id']);

        $refundAmount = $this->faker->numberBetween(0, $payment['due']);

        $results = $this->call('PUT', '/refund', [
            'payment_id'    => $payment['id'],
            'note'          => '',
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
