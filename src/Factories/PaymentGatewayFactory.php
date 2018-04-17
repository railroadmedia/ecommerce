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
        $type = '',
        $name = '',
        $configName = '',
        $brand = ''
    ) {
        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                $this->faker->randomElement([
                    'stripe',
                    'paypal'
                ]),
                $this->faker->text,
                'stripe_1',
                ConfigService::$brand,

            ];
        return parent::store(...$parameters);
    }
}