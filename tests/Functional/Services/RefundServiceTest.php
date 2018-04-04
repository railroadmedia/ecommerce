<?php


namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\PaymentFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\RefundService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class RefundServiceTest extends EcommerceTestCase
{
    /**
     * @var RefundService
     */
    protected $classBeingTested;

    /**
     * @var PaymentFactory
     */
    protected $paymentFactory;

    public function setUp()
    {
        parent::setUp();

        $this->classBeingTested = $this->app->make(RefundService::class);
        $this->paymentFactory = $this->app->make(PaymentFactory::class);
    }

    public function test_refund()
    {
        $payment = $this->paymentFactory->store();
        $refundedAmount = $this->faker->numberBetween(0, $payment['due']);
        $note = $this->faker->text;

        $refund = $this->classBeingTested->store($payment['id'], $refundedAmount, $note);

        $this->assertArraySubset([
            'payment_id' => $payment['id'],
            'payment_amount' => $payment['due'],
            'refunded_amount' => $payment['refunded'] + $refundedAmount,
            'note' => $note,
            'external_provider' => $payment['external_provider'],
            'external_id' => $payment['external_id'],
            'created_on' => Carbon::now()->toDateTimeString()
        ], $refund);

        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['due'],
                'refunded_amount' => $payment['refunded'] + $refundedAmount,
                'note' => $note,
                'external_provider' => $payment['external_provider'],
                'external_id' => $payment['external_id'],
                'created_on' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'refunded' => $payment['refunded'] + $refundedAmount,
                'id' => $payment['id'],
                'updated_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }
}
