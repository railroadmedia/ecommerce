<?php

use Railroad\Ecommerce\ExternalHelpers\PayPal;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\RemoteStorage\Services\ConfigService;

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
          'B-13L920916G5273053'
          //  \Railroad\Ecommerce\Services\ConfigService::$paypalAPI['paypal_api_test_billing_agreement_id']
        );

        $this->assertNotEmpty($transactionId);
    }
}
