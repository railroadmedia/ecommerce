<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class CartServiceTest extends EcommerceTestCase
{
    /**
     * @var CartService
     */
    protected $classBeingTested;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(CartService::class);
    }

    public function test_add_product_to_cart()
    {
       $this->classBeingTested->addCartItem('product 1','description', 2, 10, true,
            true,
             null,
            null,
             0,
             ['product-id' => 1]);
        $cart = $this->classBeingTested->addCartItem('product 1','description', 10, 10, true,
            true,
            null,
            null,
            0,
            ['product-id' => 1]);

        $this->assertEquals($cart[0]['quantity'], 12);
        $this->assertEquals($cart[0]['totalPrice'], 120);
    }


    public function test_update_item_quantity_to_cart()
    {
        $this->classBeingTested->addItemToCart('product 1','description', 2, 10, true,
            true,
            null,
            null,
            0,
            ['product-id' => 1]);
        $cart = $this->classBeingTested->addItemToCart('product 1','description', 10, 10, true,
            true,
            null,
            null,
            0,
            ['product-id' => 1]);

        $this->assertEquals($cart[0]->quantity, 12);
        $this->assertEquals($cart[0]->totalPrice, 120);
    }

    public function test_add_items_to_cart()
    {
        $this->classBeingTested->addItemToCart('product 1','description', 2, 10, true,
            true,
            null,
            null,
            0,
            ['product-id' => 1]);

        $cart = $this->classBeingTested->addItemToCart('product 2','description', 10, 10, true,
            true,
            null,
            null,
            0,
            ['product-id' => 2]);

        $this->assertEquals(2, count($cart));
    }
}
