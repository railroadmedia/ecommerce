<?php

namespace Railroad\Ecommerce\Factories;


use Faker\Generator;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;

class PaymentMethodFactory extends PaymentMethodService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $methodType = '',
        $paymentGatewayId = null,
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
        $currency = '',
        $userId = null,
        $customerId = null
    ) {
        $this->faker = app(Generator::class);
        $creditCardExpirationDate = $this->faker->creditCardExpirationDate;
        $parameters =
            func_get_args() + [
                $this->faker->randomElement([PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE]),
                rand(),
                $creditCardExpirationDate->format('Y'),
                $creditCardExpirationDate->format('m'),
                '4242424242424242',
                $this->faker->randomNumber(4),
                $this->faker->name,
                $this->faker->creditCardType,
                'EC-68Y40166KS210493B',
                rand(),
                'EUR',
                request()->user() ? request()->user()->id : null,
                request()->user() ? null : rand(),
            ];
        return parent::store(...$parameters);
    }
}