<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class SessionJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var CartAddressService
     */
    protected $cartAddressService;

    /**
     * @var Store
     */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->cartAddressService = $this->app->make(CartAddressService::class);

        $this->session = $this->app->make(Store::class);
    }

    public function test_store_address_new()
    {
        $this->session->flush();

        $cartAddressService = $this->app->make(CartAddressService::class);

        $address = $cartAddressService->getShippingAddress();

        $shippingAddress = [
            'shipping-address-line-1' => $this->faker->streetName,
            'shipping-city' => $this->faker->city,
            'shipping-first-name' => $this->faker->word
        ];

        $response = $this->call('PUT', '/session/address', $shippingAddress);

        $address
            ->setStreetLine1($shippingAddress['shipping-address-line-1'])
            ->setCity($shippingAddress['shipping-city'])
            ->setFirstName($shippingAddress['shipping-first-name']);

        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address, $addressFromSession);

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'shippingAddress' => $address->toArray()
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_store_address_supplement()
    {
        $this->session->flush();

        // setup initial session address
        $address = new Address();

        $address
            ->setStreetLine1($this->faker->address)
            ->setCity($this->faker->city)
            ->setLastName($this->faker->word)
            ->setZip($this->faker->postcode);

        $this->cartAddressService
            ->updateShippingAddress($address);

        // some default faker countries fail the backend validation, such as: 'Svalbard & Jan Mayen Islands'
        $countries = ['Canada', 'Serbia', 'Aruba', 'Greece'];

        $supplementAddress = [
            'shipping-country' => $this->faker->randomElement($countries),
            'shipping-first-name' => $this->faker->word,
        ];

        $response = $this->call('PUT', '/session/address', $supplementAddress);

        $address
            ->setCountry($supplementAddress['shipping-country'])
            ->setFirstName($supplementAddress['shipping-first-name']);

        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address, $addressFromSession);

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'shippingAddress' => $address->toArray()
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_store_address_update()
    {
        $this->session->flush();

        // setup initial session address
        $address = new Address();

        $address
            ->setStreetLine1($this->faker->address)
            ->setCity($this->faker->city)
            ->setLastName($this->faker->word)
            ->setZip($this->faker->postcode);

        $this->cartAddressService
            ->updateShippingAddress($address);

        // setup additional address data with field overwritten
        $supplementAddress = [
            'shipping-last-name' => $this->faker->word,
            'shipping-first-name' => $this->faker->word,
        ];

        $response = $this->call('PUT', '/session/address', $supplementAddress);

        $address
            ->setLastName($supplementAddress['shipping-last-name'])
            ->setFirstName($supplementAddress['shipping-first-name']);

        // assert session has the address data
        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address, $addressFromSession);

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'shippingAddress' => $address->toArray()
                ]
            ],
            $response->decodeResponseJson()
        );
    }
}
