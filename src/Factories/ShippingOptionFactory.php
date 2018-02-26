<?php

namespace Railroad\Ecommerce\Factories;


use Faker\Generator;
use Railroad\Ecommerce\Services\ShippingOptionService;

class ShippingOptionFactory extends ShippingOptionService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $country ='',
        $priority = 1,
        $active =1)
    {
        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                $this->faker->country,
                $this->faker->randomNumber(),
                $this->faker->boolean,

            ];
        return parent::store(...$parameters);
   }
}