<?php

namespace Railroad\Ecommerce\Factories;


use Faker\Generator;
use Railroad\Ecommerce\Services\PaymentMethodService;

class PaymentMethodFactory extends PaymentMethodService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $methodType='',
        $creditCardYearSelector = null,
        $creditCardMonthSelector = null,
        $fingerprint = '',
        $last4 = '',
        $cardHolderName = '',
        $companyName = '',
        $externalId = null,
        $agreementId = null,
        $expressCheckoutToken = '',
        $addressId = null,
        $userId = null,
        $customerId = null
    ) {
        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                $this->faker->randomElement([PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE]),
                rand(2018,2022),
                rand(01,12),
                $this->faker->word,
                $this->faker->randomNumber(4),
                $this->faker->name,
                $this->faker->creditCardType,
                rand(),
                rand(),
                $this->faker->word,
                rand(),
                rand(),
                rand()
            ];
        return parent::store(...$parameters);
    }
}