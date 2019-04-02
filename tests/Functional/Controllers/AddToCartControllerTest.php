<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class AddToCartControllerTest extends EcommerceTestCase
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

    public function test_add_to_cart()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
        ]);

        $initialQuantity = 2;

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [$product['sku'] => $initialQuantity],
        ]);

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the session has addedProducts key
        $response->assertSessionHas('addedProducts');

        $response->assertSessionHas('cartNumberOfItems', 1);

        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(1, count($cart->getItems()));

        // assert cart item
        $cartItemOne = $cart->getItemBySku($product['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($initialQuantity, $cartItemOne->getQuantity());
    }

    public function test_add_product_with_stock_empty_to_cart()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => 0,
        ]);

        $quantity = $this->faker->numberBetween(2, 10);
        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
        ]);

        // assert the session has the messages set on false
        $response->assertSessionHas('success', false);

        // assert the items was not added to cart
        $response->assertSessionHas('addedProducts', []);
        $response->assertSessionHas('cartNumberOfItems', 0);

        $em = $this->app->make(EcommerceEntityManager::class);

        $productEntity = $em->getRepository(Product::class)
            ->find($product['id']);

        // assert the session has the error message
        $response->assertSessionHas('notAvailableProducts', [
            [
                'message' => 'Product with SKU:' .
                    $product['sku'] .
                    ' could not be added to cart. The product stock(' .
                    $product['stock'] .
                    ') is smaller than the quantity you\'ve selected(' .
                    $quantity .
                    ')',
                'product' => $productEntity,
            ],
        ]);
    }

    public function test_add_inexistent_product_to_cart()
    {
        $randomSku = $this->faker->word;
        $response = $this->call('GET', '/add-to-cart', [
            'products' => [$randomSku => 10],
        ]);

        // assert the session has the success message set to false
        $response->assertSessionHas('success', false);

        //assert the item was not added to the cart
        $response->assertSessionHas('addedProducts', []);
        $response->assertSessionHas('cartNumberOfItems', 0);

        // assert the session has the error message
        $response->assertSessionHas('notAvailableProducts', [
            [
                'message' => 'Product with SKU:' . $randomSku . ' could not be added to cart.',
                'product' => null,
            ],
        ]);
    }

    public function test_add_many_products_to_cart()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productOneQuantity = 2;
        $productTwoQuantity = 2;

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [
                $productOne['sku'] => $productOneQuantity,
                $productTwo['sku'] => $productTwoQuantity,
            ],
        ]);

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        //assert the items were added to the cart
        $response->assertSessionHas('addedProducts');
        $response->assertSessionHas('cartNumberOfItems', 2);
        $response->assertSessionHas('notAvailableProducts', []);

        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(2, count($cart->getItems()));

        // assert cart item one
        $cartItemOne = $cart->getItemBySku($productOne['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($productOneQuantity, $cartItemOne->getQuantity());

        // assert cart item two
        $cartItemTwo = $cart->getItemBySku($productTwo['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemTwo));

        $this->assertEquals($productTwoQuantity, $cartItemTwo->getQuantity());
    }

    public function test_add_to_cart_higher_amount_than_product_stock()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(1, 3),
        ]);

        $quantity = $this->faker->numberBetween(5, 100);

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
        ]);

        // assert the session has the success message set to false
        $response->assertSessionHas('success', false);

        // assert the product was not added into the cart
        $response->assertSessionHas('addedProducts', []);
        $response->assertSessionHas('cartNumberOfItems', 0);

        $em = $this->app->make(EcommerceEntityManager::class);

        $productEntity = $em->getRepository(Product::class)
            ->find($product['id']);

        // assert the session has the error message
        $response->assertSessionHas('notAvailableProducts', [
            [
                'message' => 'Product with SKU:' .
                    $product['sku'] .
                    ' could not be added to cart. The product stock(' .
                    $product['stock'] .
                    ') is smaller than the quantity you\'ve selected(' .
                    $quantity .
                    ')',
                'product' => $productEntity,
            ],
        ]);
    }

    public function test_add_products_available_and_not_available_to_cart()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
        ]);

        $randomSku1 = $this->faker->word . 'sku1';
        $randomSku2 = $this->faker->word . 'sku2';

        $productOneQuantity = $this->faker->numberBetween(1, 5);
        $productTwoQuantity = $this->faker->numberBetween(1, 5);

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [
                $productOne['sku'] => $productOneQuantity,
                $randomSku1 => 2,
                $productTwo['sku'] => $productTwoQuantity,
                $randomSku2 => 2,
            ],
        ]);

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        //assert valid items was added into the cart
        $response->assertSessionHas('addedProducts');
        $response->assertSessionHas('cartNumberOfItems', 2);

        // assert the session has the error messages for the invalid products
        $response->assertSessionHas('notAvailableProducts', [
            [
                'message' => 'Product with SKU:' . $randomSku1 . ' could not be added to cart.',
                'product' => null,
            ],
            [
                'message' => 'Product with SKU:' . $randomSku2 . ' could not be added to cart.',
                'product' => null,
            ],
        ]);

        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(2, count($cart->getItems()));

        // assert cart item one
        $cartItemOne = $cart->getItemBySku($productOne['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($productOneQuantity, $cartItemOne->getQuantity());

        // assert cart item two
        $cartItemTwo = $cart->getItemBySku($productTwo['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemTwo));

        $this->assertEquals($productTwoQuantity, $cartItemTwo->getQuantity());
    }
}
