<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaymentServiceTest extends EcommerceTestCase
{
    /**
     * @var PaymentService
     */
    private $classBeingTested;

    /**
     * @var PaymentMethodFactory
     */
    private $paymentMethodFactory;

    /**
     * @var PaymentGatewayFactory
     */
    private $paymentGatewayFactory;

    public function setUp()
    {
        parent::setUp();
        $this->classBeingTested      = $this->app->make(PaymentService::class);
        $this->paymentMethodFactory  = $this->app->make(PaymentMethodFactory::class);
        $this->paymentGatewayFactory = $this->app->make(PaymentGatewayFactory::class);
    }

    public function test_store_payment()
    {
        $this->createAndLogInNewUser();
        $due              = 10;
        $paid             = null;
        $refunded         = null;
        $message          = '';
        $paymentGateway   = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal', 'paypal_1');
        $paymentMethod    = $this->paymentMethodFactory->store(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE, $paymentGateway['id']);

        $payment = $this->classBeingTested->store(
            $due,
            $paid,
            $refunded,
            $paymentMethod['id'],
            $paymentMethod['currency']);

        $this->assertArraySubset([
            'due'               => $due,
            'paid'              => $due,
            'refunded'          => $refunded,
            'type'              => PaymentService::ORDER_PAYMENT_TYPE,
            'status'            => 1,
            'message'           => $message,
            'payment_method_id' => $paymentMethod['id'],
            'currency'          => $paymentMethod['currency'],
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $payment);

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            $payment
        );
    }

    public function test_store_manual_payment()
    {
        $due              = $this->faker->numberBetween(1, 2000);
        $paid             = null;
        $refunded         = null;
        $paymentMethod    = null;

        $payment = $this->classBeingTested->store($due, $paid, $refunded, $paymentMethod, 'CAD');

        $this->assertArraySubset([
            'due'               => $due,
            'paid'              => $paid,
            'refunded'          => $refunded,
            'type'              => PaymentService::ORDER_PAYMENT_TYPE,
            'external_provider' => PaymentService::MANUAL_PAYMENT_TYPE,
            'external_id'       => '',
            'status'            => 1,
            'message'           => '',
            'payment_method_id' => null,
            'currency'          => 'CAD',
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $payment);

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            $payment
        );
    }

    public function test_order_link_created_when_create_payment()
    {
        $due              = $this->faker->numberBetween(1, 2000);
        $paid             = null;
        $refunded         = null;
        $paymentMethod    = null;
        $orderId          = rand();
        $currency         = $this->faker->currencyCode;

        $payment = $this->classBeingTested->store(
            $due,
            $paid,
            $refunded,
            $paymentMethod,
            $currency,
            $orderId);

        $this->assertArraySubset([
            'due'               => $due,
            'paid'              => $paid,
            'refunded'          => $refunded,
            'type'              => PaymentService::ORDER_PAYMENT_TYPE,
            'external_provider' => PaymentService::MANUAL_PAYMENT_TYPE,
            'external_id'       => '',
            'status'            => true,
            'message'           => '',
            'currency'          => $currency,
            'payment_method_id' => null,
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $payment);

        $this->assertDatabaseHas(
            ConfigService::$tableOrderPayment,
            [
                'order_id'   => $orderId,
                'payment_id' => $payment['id']
            ]
        );

        $this->assertDatabaseMissing(
            ConfigService::$tableSubscriptionPayment,
            [
                'payment_id' => $payment['id']
            ]
        );
    }

    public function test_subscription_link_created_when_create_payment()
    {
        $due              = $this->faker->numberBetween(1, 2000);
        $paid             = null;
        $refunded         = null;
        $paymentMethod    = null;
        $orderId          = null;
        $subscriptionId   = rand();
        $currency         = $this->faker->currencyCode;

        $payment = $this->classBeingTested->store(
            $due,
            $paid,
            $refunded,
            $paymentMethod,
            $currency,
            $orderId,
            $subscriptionId
        );

        $this->assertArraySubset([
            'due'               => $due,
            'paid'              => $paid,
            'refunded'          => $refunded,
            'type'              => PaymentService::RENEWAL_PAYMENT_TYPE,
            'external_provider' => PaymentService::MANUAL_PAYMENT_TYPE,
            'external_id'       => '',
            'status'            => true,
            'message'           => '',
            'payment_method_id' => null,
            'currency'          => $currency,
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $payment);

        $this->assertDatabaseMissing(
            ConfigService::$tableOrderPayment,
            [
                'payment_id' => $payment['id']
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscriptionPayment,
            [
                'subscription_id' => $subscriptionId,
                'payment_id'      => $payment['id']
            ]
        );
    }

    public function test_store_stripe_payment()
    {
        $this->createAndLogInNewUser();
        $due              = $this->faker->numberBetween(1, 2000);
        $paid             = null;
        $refunded         = null;
        $paymentGateway   = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');
        $paymentMethod    = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE, $paymentGateway['id']);

        $payment = $this->classBeingTested->store(
            $due,
            $paid,
            $refunded,
            $paymentMethod['id'],
            $paymentMethod['currency']);

        $this->assertArraySubset([
            'due'               => $due,
            'paid'              => $due,
            'refunded'          => $refunded,
            'type'              => PaymentService::ORDER_PAYMENT_TYPE,
            'status'            => 1,
            'message'           => '',
            'payment_method_id' => $paymentMethod['id'],
            'currency'          => $paymentMethod['currency'],
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $payment);

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            $payment
        );
    }
}
