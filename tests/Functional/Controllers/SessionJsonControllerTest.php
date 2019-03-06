<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\ConfigService;
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

        $shippingAddress = [
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => $this->faker->city,
            'shipping-first-name' => $this->faker->word
        ];

        $response = $this->call('PUT', '/session/address', $shippingAddress);

        $address = new Address();

        $address
            ->setStreetLineOne($shippingAddress['shipping-address-line-1'])
            ->setCity($shippingAddress['shipping-city'])
            ->setFirstName($shippingAddress['shipping-first-name']);

        // assert session has the address data
        $response->assertSessionHas(
            CartAddressService::SESSION_KEY . ConfigService::$shippingAddressType,
            $address
        );

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
            ->setStreetLineOne($this->faker->address)
            ->setCity($this->faker->city)
            ->setLastName($this->faker->word)
            ->setZipOrPostalCode($this->faker->postcode);

        $this->cartAddressService
            ->updateAddress(
                $address,
                ConfigService::$shippingAddressType
            );

        $supplementAddress = [
            'shipping-country' => $this->faker->country,
            'shipping-first-name' => $this->faker->word,
        ];

        $response = $this->call('PUT', '/session/address', $supplementAddress);

        $address
            ->setCountry($supplementAddress['shipping-country'])
            ->setFirstName($supplementAddress['shipping-first-name']);

        // assert session has the address data
        $response->assertSessionHas(
            CartAddressService::SESSION_KEY . ConfigService::$shippingAddressType,
            $address
        );

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
            ->setStreetLineOne($this->faker->address)
            ->setCity($this->faker->city)
            ->setLastName($this->faker->word)
            ->setZipOrPostalCode($this->faker->postcode);

        $this->cartAddressService
            ->updateAddress(
                $address,
                ConfigService::$shippingAddressType
            );

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
        $response->assertSessionHas(
            CartAddressService::SESSION_KEY . ConfigService::$shippingAddressType,
            $address
        );

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
