<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Railroad\Ecommerce\Factories\AddressFactory;
use Railroad\Ecommerce\Services\AddressService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class AddressServiceTest extends EcommerceTestCase
{

    /**
     * @var AddressService
     */
    protected $classBeingTested;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(AddressService::class);
        $this->addressFactory = $this->app->make(AddressFactory::class);
    }

    public function test_store()
    {
        $address = $this->classBeingTested->store(
            $this->faker->word,
            null,
            rand(),
            null,
            $this->faker->firstName,
            $this->faker->lastName,
            $this->faker->streetAddress,
            $this->faker->streetAddress,
            $this->faker->city,
            $this->faker->postcode,
            $this->faker->word,
            $this->faker->country
        );

        $this->assertTrue(true);
    }

    public function test_getById()
    {
        $address = $this->addressFactory->store();

        $this->assertEquals($address, $this->classBeingTested->getById($address['id']));
    }

    public function test_getById_address_not_exist()
    {
        $this->assertNull( $this->classBeingTested->getById(rand()));
    }

    public function test_update_address_not_exist()
    {
        $this->assertNull($this->classBeingTested->update(rand(),[]));
    }

    public function test_update_address()
    {
        $address = $this->addressFactory->store();
        $newStreetLine1 = $this->faker->streetAddress;
        $address = $this->classBeingTested->update($address['id'],
            [
                'street_line_1' => $newStreetLine1
            ]);

        $this->assertEquals($newStreetLine1, $address['street_line_1']);
    }
}
