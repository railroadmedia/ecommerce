<?php

use Illuminate\Session\Store;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Entities\Structures\Address;

class CartAddressServiceTest extends EcommerceTestCase
{
    /**
     * @var Store
     */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->app->make(Store::class);
    }

    public function test_get_address_null()
    {
        $srv = $this->app->make(CartAddressService::class);

        $this->session->flush();

        $address = $srv->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        $this->assertEquals(null, $address);
    }

    public function test_get_address_location_default()
    {
        $srv = $this->app->make(CartAddressService::class);

        $this->session->flush();

        $address = $srv->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);

        $this->assertEquals(Address::class, get_class($address));
    }

    public function test_get_address_stored()
    {
        $srv = $this->app->make(CartAddressService::class);

        $this->session->flush();

        $storedAddress = new Address($this->faker->word, $this->faker->word);

        $addressType = CartAddressService::SHIPPING_ADDRESS_TYPE;

        $this->session->put(
            CartAddressService::SESSION_KEY . $addressType,
            $storedAddress
        );

        $address = $srv->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        $this->assertEquals($storedAddress, $address);
    }

    public function test_set_address()
    {
        $srv = $this->app->make(CartAddressService::class);

        $this->session->flush();

        $address = new Address($this->faker->word, $this->faker->word);

        $addressType = CartAddressService::SHIPPING_ADDRESS_TYPE;

        $srv->setAddress($address, $addressType);

        $sessionKey = CartAddressService::SESSION_KEY . $addressType;

        $sessionAddress = $this->session->has($sessionKey) ?
                            $this->session->get($sessionKey) : null;

        $this->assertEquals($address, $sessionAddress);
    }

    public function test_update_address_merge()
    {
        $srv = $this->app->make(CartAddressService::class);

        $this->session->flush();

        $storedCountry = $this->faker->word;
        $storedState = null;

        $storedAddress = new Address($storedCountry, $storedState);

        $addressType = CartAddressService::SHIPPING_ADDRESS_TYPE;

        $this->session->put(
            CartAddressService::SESSION_KEY . $addressType,
            $storedAddress
        );

        $newCountry = null;
        $newState = $this->faker->word;

        $newAddress = new Address($newCountry, $newState);

        $srv->updateAddress($newAddress, $addressType);

        $sessionKey = CartAddressService::SESSION_KEY . $addressType;

        $sessionAddress = $this->session->has($sessionKey) ?
                            $this->session->get($sessionKey) : null;

        $this->assertEquals(Address::class, get_class($sessionAddress));

        $this->assertEquals($storedCountry, $sessionAddress->getCountry());

        $this->assertEquals($newState, $sessionAddress->getState());
    }

    public function test_update_address_new()
    {
        $srv = $this->app->make(CartAddressService::class);

        $this->session->flush();

        $storedCountry = $this->faker->word;
        $storedState = null;

        $storedAddress = new Address($storedCountry, $storedState);

        $addressType = CartAddressService::SHIPPING_ADDRESS_TYPE;

        $this->session->put(
            CartAddressService::SESSION_KEY . $addressType,
            $storedAddress
        );

        $newCountry = $this->faker->word;
        $newState = $this->faker->word;

        $newAddress = new Address($newCountry, $newState);

        $srv->updateAddress($newAddress, $addressType);

        $sessionKey = CartAddressService::SESSION_KEY . $addressType;

        $sessionAddress = $this->session->has($sessionKey) ?
                            $this->session->get($sessionKey) : null;

        $this->assertEquals(Address::class, get_class($sessionAddress));

        $this->assertEquals($newCountry, $sessionAddress->getCountry());

        $this->assertEquals($newState, $sessionAddress->getState());
    }
}
