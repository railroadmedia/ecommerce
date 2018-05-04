<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\PaymentFactory;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Services\RefundService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class RefundServiceTest extends EcommerceTestCase
{
    /**
     * @var RefundService
     */
    private $classBeingTested;

    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

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

        $this->classBeingTested      = $this->app->make(RefundService::class);
        $this->paymentFactory        = $this->app->make(PaymentFactory::class);
        $this->paymentMethodFactory  = $this->app->make(PaymentMethodFactory::class);
        $this->paymentGatewayFactory = $this->app->make(PaymentGatewayFactory::class);
    }

    public function test_refund_stripe()
    {
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');
        $paymentMethod  = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE, $paymentGateway['id']);

        $payment = $this->paymentFactory->store($this->faker->randomNumber(2),
            0,
            0,
            $this->faker->randomElement([PaymentService::RENEWAL_PAYMENT_TYPE, PaymentService::ORDER_PAYMENT_TYPE]),
            $paymentMethod['id']);

        $refundedAmount = $this->faker->numberBetween(1, $payment['due']);
        $note           = 'duplicate';

        $refund = $this->classBeingTested->store($payment['id'], $refundedAmount, $note);

        $this->assertArraySubset([
            'payment_id'        => $payment['id'],
            'payment_amount'    => $payment['due'],
            'refunded_amount'   => $payment['refunded'] + $refundedAmount,
            'note'              => $note,
            'external_provider' => $payment['external_provider'],
            'created_on'        => Carbon::now()->toDateTimeString()
        ], $refund);

        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id'        => $payment['id'],
                'payment_amount'    => $payment['due'],
                'refunded_amount'   => $payment['refunded'] + $refundedAmount,
                'note'              => $note,
                'external_provider' => $payment['external_provider'],
                'created_on'        => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'refunded'   => $payment['refunded'] + $refundedAmount,
                'id'         => $payment['id'],
                'updated_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_refund_paypal()
    {
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal', 'paypal_1');
        $paymentMethod  = $this->paymentMethodFactory->store(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE, $paymentGateway['id']);
        $due            = $this->faker->randomNumber(2);

        $payment = $this->paymentFactory->store($due,
            $due,
            0,
            $this->faker->randomElement([PaymentService::RENEWAL_PAYMENT_TYPE, PaymentService::ORDER_PAYMENT_TYPE]),
            $paymentMethod['id']);

        $refundedAmount = rand(1, $due - 1);
        $note           = 'duplicate';

        $refund = $this->classBeingTested->store($payment['id'], $refundedAmount, $note);

        $this->assertArraySubset([
            'payment_id'        => $payment['id'],
            'payment_amount'    => $payment['due'],
            'refunded_amount'   => $payment['refunded'] + $refundedAmount,
            'note'              => $note,
            'external_provider' => $payment['external_provider'],
            'created_on'        => Carbon::now()->toDateTimeString()
        ], $refund);

        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id'        => $payment['id'],
                'payment_amount'    => $payment['due'],
                'refunded_amount'   => $payment['refunded'] + $refundedAmount,
                'note'              => $note,
                'external_provider' => $payment['external_provider'],
                'created_on'        => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'refunded'   => $payment['refunded'] + $refundedAmount,
                'id'         => $payment['id'],
                'updated_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_refund_stripe_incorrect_amount()
    {
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');
        $paymentMethod  = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE, $paymentGateway['id']);

        $payment = $this->paymentFactory->store($this->faker->randomNumber(2),
            0,
            0,
            $this->faker->randomElement([PaymentService::RENEWAL_PAYMENT_TYPE, PaymentService::ORDER_PAYMENT_TYPE]),
            $paymentMethod['id']);

        $refundedAmount = $this->faker->randomElement([0, $this->faker->numberBetween($payment['due'] + 1, 999)]);
        $note           = 'duplicate';

        $refund = $this->classBeingTested->store($payment['id'], $refundedAmount, $note);

        $this->assertNull($refund);
    }

    public function test_refund_paypal_incorrect_amount()
    {
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal', 'paypal_1');
        $paymentMethod  = $this->paymentMethodFactory->store(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE, $paymentGateway['id']);

        $payment = $this->paymentFactory->store($this->faker->randomNumber(2),
            0,
            0,
            $this->faker->randomElement([PaymentService::RENEWAL_PAYMENT_TYPE, PaymentService::ORDER_PAYMENT_TYPE]),
            $paymentMethod['id']);

        $refundedAmount = $this->faker->randomElement([0, $this->faker->numberBetween($payment['due'] + 1, 999)]);
        $note           = 'duplicate';

        $refund = $this->classBeingTested->store($payment['id'], $refundedAmount, $note);

        $this->assertNull($refund);
    }
}
