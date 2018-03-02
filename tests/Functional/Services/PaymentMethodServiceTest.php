<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaymentMethodServiceTest extends EcommerceTestCase
{
    protected $classBeingTested;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(PaymentMethodService::class);
    }

    public function test_store()
    {
        $paymentMethod = $this->classBeingTested->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE, PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,

            2018,
            12,
            '',
            1234,
            '',
            '',
            123,
            'stripe',
            null,
            '',
            '');
        dd($paymentMethod);
    }
}
