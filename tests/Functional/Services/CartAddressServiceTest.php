<?php

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

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

    public function test_get_address_location_default()
    {
        $this->session->flush();

        $srv = $this->app->make(CartAddressService::class);

        $address = $srv->getBillingAddress();

        $this->assertEquals(Address::class, get_class($address));
    }

    public function test_get_address_stored()
    {
        $this->session->flush();

        $storedAddress = new Address($this->faker->word, $this->faker->word);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($storedAddress);

        $cart->toSession();

        $srv = $this->app->make(CartAddressService::class);

        $address = $srv->getShippingAddress();

        $this->assertEquals($storedAddress, $address);
    }

    public function test_update_address_merge()
    {
        $this->session->flush();

        $storedCountry = $this->faker->word;
        $storedRegion = null;

        $storedAddress = new Address($storedCountry, $storedRegion);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($storedAddress);

        $cart->toSession();

        $newCountry = null;
        $newRegion = $this->faker->word;

        $newAddress = new Address($newCountry, $newRegion);

        $srv = $this->app->make(CartAddressService::class);

        $srv->updateShippingAddress($newAddress);

        $cart = Cart::fromSession();

        $sessionAddress = $cart->getShippingAddress();

        $this->assertEquals(Address::class, get_class($sessionAddress));

        $this->assertEquals($newCountry, $sessionAddress->getCountry());

        $this->assertEquals($newRegion, $sessionAddress->getRegion());
    }

    public function test_update_address_new()
    {
        $this->session->flush();

        $storedCountry = $this->faker->word;
        $storedRegion = null;

        $storedAddress = new Address($storedCountry, $storedRegion);

        $cart = Cart::fromSession();

        $cart->setShippingAddress($storedAddress);

        $cart->toSession();

        $newCountry = $this->faker->word;
        $newRegion = $this->faker->word;

        $newAddress = new Address($newCountry, $newRegion);

        $srv = $this->app->make(CartAddressService::class);

        $srv->updateShippingAddress($newAddress);

        $cart = Cart::fromSession();

        $sessionAddress = $cart->getShippingAddress();

        $this->assertEquals(Address::class, get_class($sessionAddress));

        $this->assertEquals($newCountry, $sessionAddress->getCountry());

        $this->assertEquals($newRegion, $sessionAddress->getRegion());
    }
}
