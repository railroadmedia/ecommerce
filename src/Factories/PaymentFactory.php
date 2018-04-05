<?php

namespace Railroad\Ecommerce\Factories;


use Faker\Generator;
use Railroad\Ecommerce\Services\PaymentService;

class PaymentFactory extends PaymentService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $due ='',
        $paid ='',
        $refunded = '',
        $type ='',
        $externalProvider='',
        $externalId = null,
        $status = false,
        $message = '',
        $paymentMethodId = null,
        $currency = '',
        $orderId = null,
        $subscriptionId = null
    ) {
        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                $this->faker->randomNumber(2),
                0,
                0,
                $this->faker->randomElement([PaymentService::RENEWAL_PAYMENT_TYPE, PaymentService::ORDER_PAYMENT_TYPE]),
                '',
                null,
                false,
                '',
                rand(),
                $this->faker->currencyCode,
                rand(),
                rand()
            ];
        return parent::store(...$parameters);
    }
}