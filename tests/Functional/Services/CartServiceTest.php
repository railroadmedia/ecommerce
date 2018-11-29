<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class CartServiceTest extends EcommerceTestCase
{
    /**
     * @var CartService
     */
    protected $classBeingTested;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(CartService::class);
        $this->productRepository = $this->app->make(ProductRepository::class);

    }

    public function test_add_product_to_cart()
    {
        $product = $this->productRepository->create($this->faker->product());

        $this->classBeingTested->addCartItem(
            'product 1',
            'description',
            2,
            $product['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $product['id']]
        );
        $cart = $this->classBeingTested->addCartItem(
            'product 1',
            'description',
            10,
            $product['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $product['id']]
        );

        $this->assertEquals($cart->getItems()[0]->getQuantity(), 12);
        $this->assertEquals($cart->getItems()[0]->getTotalPrice(), 12 * $product['price']);
    }

    public function test_update_item_quantity_to_cart()
    {
        $product = $this->productRepository->create($this->faker->product());

        $this->classBeingTested->addCartItem(
            'product 1',
            'description',
            2,
            $product['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $product['id']]
        );
        $cart = $this->classBeingTested->addCartItem(
            'product 1',
            'description',
            10,
            $product['price'],
            true,
            true,
            null,
            null,
            ['product-id' => $product['id']]
        );

        $this->assertEquals($cart->getItems()[0]->quantity, 12);
        $this->assertEquals($cart->getItems()[0]->totalPrice, 12*$product['price']);
    }

    public function test_add_items_to_cart()
    {
        $product = $this->productRepository->create($this->faker->product());
        $product2 = $this->productRepository->create($this->faker->product());

        $this->classBeingTested->addCartItem(
            'product 1',
            'description',
            2,
            10,
            true,
            true,
            null,
            null,
            ['product-id' => $product['id']]
        );

        $cart = $this->classBeingTested->addCartItem(
            'product 2',
            'description',
            10,
            10,
            true,
            true,
            null,
            null,
            ['product-id' => $product2['id']]
        );

        $this->assertEquals(1, count($cart));
        $this->assertEquals(2, count($cart->getItems()));
    }
}
