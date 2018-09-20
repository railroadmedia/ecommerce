<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class SessionJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var CartAddressService
     */
    protected $cartAddressService;

    protected function setUp()
    {
        parent::setUp();

        $this->cartAddressService = $this->app->make(CartAddressService::class);
    }

    public function test_store_address_new()
    {
        $shippingAddress = [
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => $this->faker->city,
            'shipping-first-name' => $this->faker->word
        ];

        $response = $this->call('PUT', '/session/address', $shippingAddress);

        $sessionAddress = [
            'streetLineOne' => $shippingAddress['shipping-address-line-1'],
            'city' => $shippingAddress['shipping-city'],
            'firstName' => $shippingAddress['shipping-first-name'],
        ];


        // assert session has the address data
        $response->assertSessionHas(
            CartAddressService::SESSION_KEY . ConfigService::$shippingAddressType,
            $sessionAddress
        );

        // assert response has the address data
        $this->assertArraySubset(
            [ConfigService::$shippingAddressType => $sessionAddress],
            $response->decodeResponseJson()['data'][0]
        );
    }

    public function test_store_address_supplement()
    {
        // setup initial session address
        $initialAddress = [
            'streetLineOne' => $this->faker->address,
            'city' => $this->faker->city,
            'lastName' => $this->faker->word,
            'zipOrPostalCode' => $this->faker->postcode,
        ];

        $this->cartAddressService
            ->updateAddress(
                $initialAddress,
                ConfigService::$shippingAddressType
            );

        // setup additional address data
        $supplementAddress = [
            'shipping-country' => $this->faker->country,
            'shipping-first-name' => $this->faker->word,
        ];

        $response = $this->call('PUT', '/session/address', $supplementAddress);

        $sessionAddress = array_merge(
            $initialAddress,
            [
                'country' => $supplementAddress['shipping-country'],
                'firstName' => $supplementAddress['shipping-first-name'],
            ]
        );

        // assert session has the address data
        $response->assertSessionHas(
            CartAddressService::SESSION_KEY . ConfigService::$shippingAddressType,
            $sessionAddress
        );

        // assert response has the address data
        $this->assertArraySubset(
            [ConfigService::$shippingAddressType => $sessionAddress],
            $response->decodeResponseJson()['data'][0]
        );
    }

    public function test_store_address_update()
    {
        // setup initial session address
        $initialAddress = [
            'streetLineOne' => $this->faker->address,
            'city' => $this->faker->city,
            'lastName' => $this->faker->word,
            'zipOrPostalCode' => $this->faker->postcode,
        ];

        $this->cartAddressService
            ->updateAddress(
                $initialAddress,
                ConfigService::$shippingAddressType
            );

        // setup additional address data with  field overwritten
        $supplementAddress = [
            'shipping-last-name' => $this->faker->word,
            'shipping-first-name' => $this->faker->word,
        ];

        $response = $this->call('PUT', '/session/address', $supplementAddress);

        $sessionAddress = array_merge(
            $initialAddress,
            [
                'lastName' => $supplementAddress['shipping-last-name'],
                'firstName' => $supplementAddress['shipping-first-name'],
            ]
        );

        // assert session has the address data
        $response->assertSessionHas(
            CartAddressService::SESSION_KEY . ConfigService::$shippingAddressType,
            $sessionAddress
        );

        // assert response has the address data
        $this->assertArraySubset(
            [ConfigService::$shippingAddressType => $sessionAddress],
            $response->decodeResponseJson()['data'][0]
        );
    }
}
