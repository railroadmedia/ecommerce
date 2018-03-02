<?php

namespace Railroad\Ecommerce\Factories;


use Faker\Generator;
use Railroad\Ecommerce\Services\ShippingCostsService;

class ShippingCostsFactory extends ShippingCostsService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $shippingOptionId = null,
        $min = 0,
        $max = 0,
        $price = 0
    ) {
        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                rand(),
                rand(0,100),
                rand(100,500),
                rand(0,5000)
            ];
        return parent::store(...$parameters);
    }
}