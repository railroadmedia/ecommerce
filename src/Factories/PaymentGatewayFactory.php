<?php

namespace Railroad\Ecommerce\Factories;

use Carbon\Carbon;
use Faker\Generator;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Services\ConfigService;

class PaymentGatewayFactory extends PaymentGatewayRepository
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $type = null,
        $name = null,
        $configName = null,
        $brand = null
    ) {
        $this->faker = app(Generator::class);

        $parameters = [
                'type' => $type ?? $this->faker->randomElement(
                    [
                        'stripe',
                        'paypal',
                    ]
                ),
                'name' => $name ?? $this->faker->text,
                'config' => $configName ?? 'stripe_1',
                'brand' => $brand ?? ConfigService::$brand,
                'created_on' => Carbon::now()->toDateTimeString(),
            ];

        return parent::create($parameters);
    }
}