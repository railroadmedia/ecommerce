<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ShippingService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingServiceTest extends EcommerceTestCase
{
    /**
     * @var ShippingService
     */
    protected $shippingService;

    protected function setUp()
    {
        parent::setUp();

        $this->shippingService = app()->make(ShippingService::class);
    }

    public function test_get_weight_empty_cart()
    {
        $this->assertEmpty($this->shippingService->getCartWeight(new Cart()));
    }

    public function test_get_weight_single_product()
    {
        $weight = 10;
        $quantity = $this->faker->numberBetween(1, 10);

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'weight' => $weight,
            ]
        );

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart($product['sku'], $quantity);

        $this->assertEquals($weight * $quantity, $this->shippingService->getCartWeight($cartService->getCart()));
    }

    public function test_get_weight_multiple_product()
    {
        $weight1 = 10;
        $weight2 = 5.25;

        $quantity = $this->faker->numberBetween(1, 10);

        $product1 = $this->fakeProduct(
            [
                'active' => 1,
                'weight' => $weight1,
            ]
        );
        $product2 = $this->fakeProduct(
            [
                'active' => 1,
                'weight' => $weight2,
            ]
        );

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart($product1['sku'], $quantity);
        $cartService->addToCart($product2['sku'], $quantity);

        $this->assertEquals(
            ($product1['weight'] + $product2['weight']) * $quantity,
            $this->shippingService->getCartWeight($cartService->getCart())
        );
    }

    public function test_get_shipping_due_for_empty_cart()
    {
        $this->assertEmpty($this->shippingService->getShippingDueForCart(new Cart()));
    }

    public function test_get_shipping_due_for_single_product()
    {
        $weight = 10;
        $quantity = 1;
        $country = 'Canada';
        $price = 15.5;

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'weight' => $weight,
            ]
        );

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => $country,
                'active' => true,
            ]
        );

        $shippingCost = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 1,
                'max' => 100,
                'price' => $price,
            ]
        );

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart($product['sku'], $quantity);
        $cartService->setBillingAddress(new Address($country));

        $this->assertEquals(
            $price,
            $this->shippingService->getShippingDueForCart($cartService->getCart())
        );
    }

    public function test_get_shipping_due_for_multiple_products()
    {
        $weight1 = 10;
        $weight2 = 3.3;
        $quantity = 1;
        $country = 'Canada';
        $price = 15.5;

        $product1 = $this->fakeProduct(
            [
                'active' => 1,
                'weight' => $weight1,
            ]
        );

        $product2 = $this->fakeProduct(
            [
                'active' => 1,
                'weight' => $weight2,
            ]
        );

        $shippingOption = $this->fakeShippingOption(
            [
                'country' => $country,
                'active' => true,
            ]
        );

        // it should use this one
        $shippingCost1 = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 1,
                'max' => 100,
                'price' => $price,
            ]
        );

        $shippingCost2 = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => 100,
                'max' => 500,
                'price' => 1000,
            ]
        );

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart($product1['sku'], $quantity);
        $cartService->addToCart($product2['sku'], $quantity);
        $cartService->setBillingAddress(new Address($country));

        $this->assertEquals(
            $price,
            $this->shippingService->getShippingDueForCart($cartService->getCart())
        );
    }

    public function test_get_shipping_due_multiple_overlapping_options_use_first()
    {
        $weight = 10;
        $quantity = 1;
        $country = 'Canada';
        $price1 = 15.5;
        $price2 = 500.1;

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'weight' => $weight,
            ]
        );

        $shippingOption1 = $this->fakeShippingOption(
            [
                'country' => $country,
                'active' => true,
            ]
        );

        $shippingOption2 = $this->fakeShippingOption(
            [
                'country' => $country,
                'active' => true,
            ]
        );

        $shippingCost1 = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption1['id'],
                'min' => 1,
                'max' => 100,
                'price' => $price1,
            ]
        );

        $shippingCost2 = $this->fakeShippingCost(
            [
                'shipping_option_id' => $shippingOption2['id'],
                'min' => 1,
                'max' => 100,
                'price' => $price2,
            ]
        );

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart($product['sku'], $quantity);
        $cartService->setBillingAddress(new Address($country));

        $this->assertEquals(
            $price1,
            $this->shippingService->getShippingDueForCart($cartService->getCart())
        );
    }

}
