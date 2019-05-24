<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class SessionJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var CartAddressService
     */
    protected $cartAddressService;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var Store
     */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->cartAddressService = $this->app->make(CartAddressService::class);
        $this->cartService = $this->app->make(CartService::class);

        $this->session = $this->app->make(Store::class);
    }

    public function test_store_address_new()
    {
        $this->session->flush();

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(15, 100),
                'is_physical' => true,
                'weight' => 10,
            ]
        );

        $this->cartService->addToCart($product['sku'], 1);

        $cartAddressService = $this->app->make(CartAddressService::class);

        $address = $cartAddressService->getShippingAddress();

        $shippingAddress = [
            'shipping-address-line-1' => $this->faker->streetName,
            'shipping-city' => $this->faker->city,
            'shipping-first-name' => $this->faker->word
        ];

        $response = $this->call('PUT', '/session/address', $shippingAddress);

        $address->setStreetLine1($shippingAddress['shipping-address-line-1']);
        $address->setCity($shippingAddress['shipping-city']);
        $address->setFirstName($shippingAddress['shipping-first-name']);

        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address, $addressFromSession);

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'cart' => [
                        'shipping_address' => $address->toArray()
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_store_address_existing_id()
    {
        $this->session->flush();

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(15, 100),
                'is_physical' => true,
                'weight' => 10,
            ]
        );

        $address = $this->fakeAddress();

        $this->cartService->addToCart($product['sku'], 1);

        $response = $this->call('PUT', '/session/address', ['shipping-address-id' => $address['id']]);

        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $expectedAddress = [
            'first_name' => $address['first_name'],
            'last_name' => $address['last_name'],
            'city' => $address['city'],
            'state' => $address['state'],
            'country' => $address['country'],
            'zip_or_postal_code' => $address['zip'],
            'street_line_two' => $address['street_line_2'],
            'street_line_one' => $address['street_line_1'],
        ];

        $this->assertEquals($expectedAddress, $addressFromSession->toArray());

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'cart' => [
                        'shipping_address' => $expectedAddress
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_store_address_supplement()
    {
        $this->session->flush();

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(15, 100),
                'is_physical' => true,
                'weight' => 10,
            ]
        );

        $this->cartService->addToCart($product['sku'], 1);

        // setup initial session address
        $address = new Address();

        $address->setStreetLine1($this->faker->address);
        $address->setCity($this->faker->city);
        $address->setLastName($this->faker->word);
        $address->setZip($this->faker->postcode);

        $this->cartAddressService->updateShippingAddress($address);

        $supplementAddress = [
            'shipping-country' => 'Serbia',
            'shipping-first-name' => $this->faker->word,
        ];

        $response = $this->call('PUT', '/session/address', $supplementAddress);

        $address->setCountry($supplementAddress['shipping-country']);
        $address->setFirstName($supplementAddress['shipping-first-name']);

        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address, $addressFromSession);

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'cart' => [
                        'shipping_address' => $address->toArray()
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_store_address_update()
    {
        $this->session->flush();

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(15, 100),
                'is_physical' => true,
                'weight' => 10,
            ]
        );

        $this->cartService->addToCart($product['sku'], 1);

        // setup initial session address
        $address = new Address();

        $address->setStreetLine1($this->faker->address);
        $address->setCity($this->faker->city);
        $address->setLastName($this->faker->word);
        $address->setZip($this->faker->postcode);

        $this->cartAddressService->updateShippingAddress($address);

        // setup additional address data with field overwritten
        $supplementAddress = [
            'shipping-last-name' => $this->faker->word,
            'shipping-first-name' => $this->faker->word,
        ];

        $response = $this->call('PUT', '/session/address', $supplementAddress);

        $address->setLastName($supplementAddress['shipping-last-name']);
        $address->setFirstName($supplementAddress['shipping-first-name']);

        // assert session has the address data
        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address, $addressFromSession);

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'cart' => [
                        'shipping_address' => $address->toArray()
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );
    }
}
