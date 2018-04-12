<?php

use Railroad\Ecommerce\ExternalHelpers\PayPal;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaypalTest extends EcommerceTestCase
{
    protected $classBeingTested;

    public function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(PayPal::class);
    }

    public function test_create_billing_agreement_express_checkout_token()
    {
        $expressCheckoutToken = $this->classBeingTested->createBillingAgreementExpressCheckoutToken(
            $this->faker->url,
            $this->faker->url
        );

        $this->assertNotEmpty($expressCheckoutToken);
    }

    public function test_create_and_confirm_billing_agreement_bad_token()
    {
        $this->expectException('Railroad\Ecommerce\Exceptions\PayPal\CreateBillingAgreementException');

        $billingAgreementId = $this->classBeingTested->confirmAndCreateBillingAgreement($this->faker->text);
    }

    public function test_create_reference_transaction()
    {
        $amount = $this->faker->numberBetween(5, 1000);
        $description = $this->faker->sentence;

        $transactionId = $this->classBeingTested->createReferenceTransaction(
            $amount,
            $description,
            'B-6JD49251BA637280M'
            //ConfigService::$paypalAPI['paypal_api_test_billing_agreement_id']
        );

        $this->assertNotEmpty($transactionId);
    }

    public function test_create_reference_transaction_amount_to_high()
    {
        $amount = $this->faker->numberBetween(100000, 200000);
        $description = $this->faker->sentence;

        $this->expectException(
            'Railroad\Ecommerce\Exceptions\PayPal\CreateReferenceTransactionException'
        );

        $transactionId = $this->classBeingTested->createReferenceTransaction(
            $amount,
            $description,
            ConfigService::$paypalAPI['paypal_api_test_billing_agreement_id']
        );
    }

    public function test_create_reference_transaction_invalid_billing_agreement()
    {
        $amount = $this->faker->numberBetween(100000, 200000);
        $description = $this->faker->sentence;

        $this->expectException(
            'Railroad\Ecommerce\Exceptions\PayPal\CreateReferenceTransactionException'
        );

        $transactionId = $this->classBeingTested->createReferenceTransaction(
            $amount,
            $description,
            'bad agreement id'
        );
    }

    public function test_create_refund_full()
    {
        $amount = $this->faker->numberBetween(5, 1000);
        $description = $this->faker->sentence;

        $transactionId = $this->classBeingTested->createReferenceTransaction(
            $amount,
            $description,
            ConfigService::$paypalAPI['paypal_api_test_billing_agreement_id']
        );

        $refundId = $this->classBeingTested->createTransactionRefund(
            $amount,
            false,
            $transactionId,
            'duplicate'
        );

        $this->assertNotEmpty($refundId);
    }

    public function test_create_refund_partial()
    {
        $amount = $this->faker->numberBetween(500, 1000);
        $description = $this->faker->sentence;

        $transactionId = $this->classBeingTested->createReferenceTransaction(
            $amount,
            $description,
            ConfigService::$paypalAPI['paypal_api_test_billing_agreement_id']
        );

        $refundId = $this->classBeingTested->createTransactionRefund(250, true, $transactionId, 'duplicate');

        $this->assertNotEmpty($refundId);

        $refundId = $this->classBeingTested->createTransactionRefund(250, true, $transactionId, 'duplicate');

        $this->assertNotEmpty($refundId);
    }

    public function test_create_refund_partial_mismatch()
    {
        $amount = $this->faker->numberBetween(500, 1000);
        $description = $this->faker->sentence;

        $transactionId = $this->classBeingTested->createReferenceTransaction(
            $amount,
            $description,
            ConfigService::$paypalAPI['paypal_api_test_billing_agreement_id']
        );

        $this->expectException(
            'Railroad\Ecommerce\Exceptions\PayPal\CreateRefundException'
        );

        $refundId = $this->classBeingTested->createTransactionRefund(250, false, $transactionId, 'duplicate');
    }

    public function test_create_refund_amount_too_high()
    {
        $amount = $this->faker->numberBetween(1, 100);
        $description = $this->faker->sentence;

        $transactionId = $this->classBeingTested->createReferenceTransaction(
            $amount,
            $description,
            'B-3UY75255FC877710X'
            //ConfigService::$paypalAPI['paypal_api_test_billing_agreement_id']
        );

        $this->expectException(
            'Railroad\Ecommerce\Exceptions\PayPal\CreateRefundException'
        );

        $refundId = $this->classBeingTested->createTransactionRefund(2000, false, $transactionId, 'duplicate');
    }

    public function test_create_refund_transaction_not_found()
    {
        $this->expectException(
            'Railroad\Ecommerce\Exceptions\PayPal\CreateRefundException'
        );

        $refundId = $this->classBeingTested->createTransactionRefund(2000, false, 'bad id', 'duplicate');
    }
}
