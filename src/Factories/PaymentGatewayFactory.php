<?php

namespace Railroad\Ecommerce\Factories;

use Faker\Generator;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentGatewayService;


class PaymentGatewayFactory extends PaymentGatewayService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $brand = '',
        $type = '',
        $name = '',
        $configName = ''
    ) {
        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                ConfigService::$brand,
                $this->faker->randomElement([
                    'stripe',
                    'paypal'
                ]),
                $this->faker->text,
                'stripe-1',

            ];
        return parent::store(...$parameters);
    }
}