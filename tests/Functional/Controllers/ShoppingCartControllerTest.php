<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Cart;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShoppingCartControllerTest extends EcommerceTestCase
{
    use WithoutMiddleware;

    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    protected $productRepository;

    /**
     * @var Cart
     */
    private $cart;

    protected function setUp()
    {
        parent::setUp();

        $this->productRepository = $this->app->make(ProductRepository::class);

        $this->cart = app()->make(Cart::class);

        $this->cart->clear();
    }

    public function test_cart_session_integrity()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(15, 100),
                ]
            )
        );

        $this->cart->addItem($product, 1);
        $this->cart->setNumberOfPayments(3);
        $this->cart->setPromoCode('code');
        $this->cart->setCurrency('CAD');
        $this->cart->setShippingAddress(new Address(['country' => 'Canada']));
        $this->cart->setBillingAddress(new Address(['country' => 'Canada']));

        $this->cart->toSession();

        $cartFromSession = app()->make(Cart::class);

        $cartFromSession->fromSession();

        $this->assertEquals($this->cart->toArray(), $cartFromSession->toArray());
        $this->assertNotEquals(spl_object_id($this->cart), spl_object_id($cartFromSession));
    }

    public function test_add_to_cart()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(15, 100),
                ]
            )
        );

        $this->call(
            'PUT',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => 1],
            ]
        );

        $response = $this->call(
            'GET',
            '/add-to-cart?products[' . $product['sku'] . ']=1'
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the product was added to the cart
        $response->assertSessionHas('addedProducts', [0 => $product]);

        $this->cart->fromSession();
    }

    public function test_add_product_with_stock_empty_to_cart()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => 0,
                ]
            )
        );

        $quantity = $this->faker->numberBetween(1, 1000);
        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $quantity],
            ]
        );

        // assert the session has the messages set on false
        $response->assertSessionHas('success', false);

        //assert the items was not added to cart
        $response->assertSessionHas('addedProducts', []);
        $response->assertSessionHas('cartNumberOfItems', 0);

        // assert the session has the error message
        $response->assertSessionHas(
            'notAvailableProducts',
            [
                [
                    'message' => 'Product with SKU:' .
                        $product['sku'] .
                        ' could not be added to cart. The product stock(' .
                        $product['stock'] .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantity .
                        ')',
                    'product' => $product,
                ],
            ]
        );
    }

    public function test_add_inexistent_product_to_cart()
    {
        $randomSku = $this->faker->word;
        $response = $this->call(
            'GET',
            '/add-to-cart',
            [
                'products' => [$randomSku => 10],
            ]
        );

        // assert the session has the success message set to false
        $response->assertSessionHas('success', false);

        //assert the item was not added to the cart
        $response->assertSessionHas('addedProducts', []);
        $response->assertSessionHas('cartNumberOfItems', 0);

        // assert the session has the error message
        $response->assertSessionHas(
            'notAvailableProducts',
            [
                [
                    'message' => 'Product with SKU:' . $randomSku . ' could not be added to cart.',
                    'product' => null,
                ],
            ]
        );
    }

    public function test_add_many_products_to_cart()
    {
        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(5, 100),
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(5, 100),
                ]
            )
        );

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $product1['sku'] => 2,
                    $product2['sku'] => 3,
                ],
            ]
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        //assert the items was added to the cart
        $response->assertSessionHas('addedProducts', [$product1, $product2]);
        $response->assertSessionHas('cartNumberOfItems', 2);
        $response->assertSessionHas('notAvailableProducts', []);
    }

    public function test_add_to_cart_higher_amount_than_product_stock()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(1, 3),
                ]
            )
        );
        $quantity = $this->faker->numberBetween(5, 100);
        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $quantity],
            ]
        );

        // assert the session has the success message set to false
        $response->assertSessionHas('success', false);

        // assert the product was not added into the cart
        $response->assertSessionHas('addedProducts', []);
        $response->assertSessionHas('cartNumberOfItems', 0);

        // assert the session has the error message
        $response->assertSessionHas(
            'notAvailableProducts',
            [
                [
                    'message' => 'Product with SKU:' .
                        $product['sku'] .
                        ' could not be added to cart. The product stock(' .
                        $product['stock'] .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantity .
                        ')',
                    'product' => $product,
                ],
            ]
        );
    }

    public function test_add_products_available_and_not_available_to_cart()
    {
        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(10, 3000),
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(10, 300),
                ]
            )
        );
        $randomSku1 = $this->faker->word . 'sku1';
        $randomSku2 = $this->faker->word . 'sku2';

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $product1['sku'] => $this->faker->numberBetween(1, 5),
                    $randomSku1 => 2,
                    $product2['sku'] => $this->faker->numberBetween(1, 5),
                    $randomSku2 => 2,
                ],
            ]
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        //assert valid items was added into the cart
        $response->assertSessionHas('addedProducts', [$product1, $product2]);
        $response->assertSessionHas('cartNumberOfItems', 2);

        // assert the session has the error messages for the invalid products
        $response->assertSessionHas(
            'notAvailableProducts',
            [
                [
                    'message' => 'Product with SKU:' . $randomSku1 . ' could not be added to cart.',
                    'product' => null,
                ],
                [
                    'message' => 'Product with SKU:' . $randomSku2 . ' could not be added to cart.',
                    'product' => null,
                ],
            ]
        );
    }

    public function test_remove_product_from_cart()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(1001, 3000),
                ]
            )
        );

        $this->cart->addItem($product, $this->faker->numberBetween(1, 1000));

        $response = $this->call('PUT', '/remove-from-cart/' . $product['id']);
        $this->cart->clear();

        // assert cart data response
        $this->assertEquals(
            [
                'data' => [
                    $this->cart->toArray(),
                ],
            ],
            $response->decodeResponseJson()
        );

        // assert the session has the success message and the product was removed from the cart
        $response->assertSessionMissing('addedProducts');

        //assert response status code
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_remove_product_from_cart_cart_not_empty()
    {
        $productOne = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(1001, 3000),
                ]
            )
        );
        $productOneQuantity = $this->faker->numberBetween(1, 1000);

        $this->cart->addItem($productOne, $productOneQuantity);

        $productTwo = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(1001, 3000),
                ]
            )
        );
        $productTwoQuantity = $this->faker->numberBetween(1, 1000);

        $this->cart->addItem($productTwo, $productTwoQuantity);

        $productThree = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(1001, 3000),
                ]
            )
        );
        $productThreeQuantity = $this->faker->numberBetween(1, 1000);

        $this->cart->addItem($productThree, $productThreeQuantity);

        $this->cart->toSession();

        $response = $this->call('PUT', '/remove-from-cart/' . $productOne['id']);

        $this->cart->fromSession();

        $this->assertEquals(2, count($this->cart->getItems()));

        // assert the session has the success message and the product was removed from the cart
        $response->assertSessionMissing('addedProducts');

        //assert response status code
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_update_cart_item_quantity()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'is_physical' => 1,
                    'stock' => $this->faker->numberBetween(6, 3000),
                ]
            )
        );

        $firstQuantity = $this->faker->numberBetween(1, 5);

        $this->cart->addItem($product, $firstQuantity);

        $newQuantity = $this->faker->numberBetween(6, 10);

        $this->cart->toSession();

        $response = $this->call('PUT', '/update-product-quantity/' . $product['id'] . '/' . $newQuantity);

        $this->cart->fromSession();

        $this->assertEquals($newQuantity, $this->cart->getItem($product['id'])->quantity);

        //assert response status code
        $this->assertEquals(201, $response->getStatusCode());

    }

    public function test_update_cart_item_quantity_insufficient_stock()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(2, 5),
                ]
            )
        );

        $firstQuantity = $this->faker->numberBetween(1, 2);

        $this->cart->addItem($product, $firstQuantity);

        $this->cart->toSession();

        $newQuantity = $this->faker->numberBetween(6, 10);
        $response = $this->call('PUT', '/update-product-quantity/' . $product['id'] . '/' . $newQuantity);

        $this->cart->fromSession();

        $this->assertEquals($firstQuantity, $this->cart->getItem($product['id'])->quantity);

        //assert response status code
        $this->assertEquals(201, $response->getStatusCode());

    }

    public function test_redirect_to_shop_with_added_product_data()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(3, 100),
                    'is_physical' => 0,
                ]
            )
        );

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => 2],
                'redirect' => '/shop',
            ]
        );

        //assert redirect was done
        $response->assertRedirect('/shop');

        //assert product info exists on session
        $response->assertSessionHas('success', true);
        $response->assertSessionHas('addedProducts', [$product]);
        $response->assertSessionHas('cartNumberOfItems', 1);
        $response->assertSessionHas('cartSubTotal');
    }

    public function test_redirect_checkout()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(1, 100),
                ]
            )
        );

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => 2],
            ],
            [],
            [],
            ['HTTP_REFERER' => '/checkout']
        );

        //assert user redirected to previous page
        $response->assertRedirect('/checkout');

        //assert product info exists on session
        $response->assertSessionHas('success', true);
        $response->assertSessionHas('addedProducts', [$product]);
        $response->assertSessionHas('cartNumberOfItems', 1);
        $response->assertSessionHas('cartSubTotal');
    }

    public function test_promo_code()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(2, 5),
                ]
            )
        );

        $promoCode = $this->faker->word;

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $product['sku'] => $this->faker->numberBetween(1, 2),
                ],
                'promo-code' => $promoCode,
            ]
        );

        $this->cart->fromSession();

        $this->assertEquals($promoCode, $this->cart->getPromoCode());
    }

    public function test_lock_cart()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(15, 100),
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(15, 100),
                    'sku' => 'DLM',
                ]
            )
        );

        $initialQuantity = 2;
        $this->call(
            'PUT',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $initialQuantity],
                'locked' => true,
            ]
        );

        $newQuantity = 10;
        $response = $this->call(
            'GET',
            '/add-to-cart?products[DLM]=1,year,1'
        );

        $this->cart->fromSession();

        $this->assertEquals(1, count($this->cart->getItems()));

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the number of items contain only the products added to cart
        $response->assertSessionHas('cartNumberOfItems', 1);

        // assert that the cart was cleared and only the second product was added to the cart
        $response->assertSessionHas('addedProducts', [0 => $product2]);
    }

    public function test_multiple_add_to_cart()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(15, 100),
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(15, 100),
                    'sku' => 'DLM',
                ]
            )
        );

        $initialQuantity = 2;
        $resp = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $initialQuantity],
            ]
        );

        $newQuantity = 10;
        $response = $this->withSession($this->app['session.store']->all())
            ->call(
                'GET',
                '/add-to-cart?products[DLM]=1,year,1'
            );

        $this->cart->fromSession();

        $this->assertEquals(2, count($this->cart->getItems()));

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        //assert the old item still exist on session
        $response->assertSessionHas('cartNumberOfItems', 2);

        // assert that the added product exists on session
        $response->assertSessionHas('addedProducts', [0 => $product2]);
    }
}
