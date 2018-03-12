<?php

namespace Railroad\Ecommerce\Factories;


use Faker\Generator;
use Railroad\Ecommerce\Services\AddressService;
use Webpatser\Countries\Countries;

class AddressFactory extends AddressService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $type = '', $brand = '', $userId = null, $customerId = null, $firstName = '', $lastName = '', $streetLine1 = '', $streetLine2 = '', $city = '', $zip = '', $state = '', $country = ''
    ) {
        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                $this->faker->randomElement([
                    AddressService::BILLING_ADDRESS,
                    AddressService::SHIPPING_ADDRESS
                ]),
                null,
                rand(),
                null,
                $this->faker->firstName,
                $this->faker->lastName,
                $this->faker->streetAddress,
                '',
                $this->faker->city,
                $this->faker->postcode,
                $this->faker->word,
                $this->faker->randomElement( array_column( Countries::getCountries(), 'full_name'))
            ];
        return parent::store(...$parameters);
    }
}