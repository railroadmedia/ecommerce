<?php

namespace Railroad\Ecommerce\Factories;


use Faker\Generator;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CustomerService;

class CustomerFactory extends CustomerService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $phone = '',
        $email = '',
        $brand = ''
    ) {
        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                $this->faker->phoneNumber,
                $this->faker->email,
               ConfigService::$brand
            ];
        return parent::store(...$parameters);
    }
}