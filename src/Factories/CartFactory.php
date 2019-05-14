<?php

namespace Railroad\Ecommerce\Factories;

use Faker\Generator;
use Railroad\Ecommerce\Services\CartService;

class CartFactory extends CartService
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @param null $contentId
     * @param null $key
     * @param null $value
     * @param null $position
     * @return array
     */
    public function addCartItem(
        $name = '',
        $description = '',
        $quantity = 0,
        $price = 0,
        $requiresShippingAddress = false,
        $requiresBillingAddress = false,
        $subscriptionIntervalType = null,
        $subscriptionIntervalCount = null,
        $options = []
    )
    {
        $this->faker = app(Generator::class);

        $parameters = func_get_args() + [
                $this->faker->word,
                $this->faker->word,
                $this->faker->numberBetween(1, 1000),
                $this->faker->numberBetween(1, 1000),
                $this->faker->boolean,
                $this->faker->boolean,
                $this->faker->word,
                0,
                [
                    'product-id' => rand()
                ]
            ];

        return parent::addCartItem(...$parameters);
    }
}