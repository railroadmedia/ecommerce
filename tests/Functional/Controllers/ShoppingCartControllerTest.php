<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Railroad\Ecommerce\Factories\CartFactory;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShoppingCartControllerTest extends EcommerceTestCase
{
    use WithoutMiddleware;

    /**
     * @var CartService
     */
    protected $classBeingTested;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    protected $productRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(CartService::class);
        $this->cartService = $this->app->make(CartService::class);
        $this->productRepository = $this->app->make(ProductRepository::class);
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

        $initialQuantity = 2;
        $this->call(
            'PUT',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $initialQuantity],
            ]
        );

        $newQuantity = 10;
        $response = $this->call(
            'GET',
            '/add-to-cart?products[' . $product['sku'] . ']=1'
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the product was added to the cart
        $response->assertSessionHas('addedProducts', [0 => $product]);
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
                    'message' =>
                        'Product with SKU:' .
                        $product['sku'] .
                        ' could not be added to cart. The product stock(' .
                        $product['stock'] .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantity .
                        ')',
                    'product' => $product
                ]
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
                    'product' => null
                ]
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
                    'product' => $product
                ]
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
                ]
            ]
        );
    }

    public function test_remove_product_from_cart()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(6, 3000),
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $this->faker->numberBetween(1, 1000),
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            0,
            [
                'product-id' => $product['id'],
            ]
        );

        $response = $this->call('PUT', '/remove-from-cart/' . $product['id']);

        // assert the session has the success message and the product was removed from the cart
        $response->assertSessionMissing('addedProducts');

        //assert response status code
        $this->assertEquals(204, $response->getStatusCode());
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
        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $firstQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            0,
            [
                'product-id' => $product['id'],
            ]
        );
        $newQuantity = $this->faker->numberBetween(6, 10);
        $response = $this->call('PUT', '/update-product-quantity/' . $product['id'] . '/' . $newQuantity);

        $decodedResponse = $response->decodeResponseJson('data');

        //assert response code status
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($decodedResponse[0]['success']);

        //assert updated cart item returned in response
        $this->assertEquals($newQuantity, $decodedResponse[0]['addedProducts'][0]['quantity']);

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

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $firstQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            0,
            rand(),
            [
                'product-id' => $product['id'],
            ]
        );

        $newQuantity = $this->faker->numberBetween(6, 10);
        $response = $this->call('PUT', '/update-product-quantity/' . $product['id'] . '/' . $newQuantity);

        $decodedResponse = $response->decodeResponseJson('data');

        //assert response
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertFalse($decodedResponse[0]['success']);
        $this->assertEquals($firstQuantity, $decodedResponse[0]['addedProducts'][0]['quantity']);

    }

    public function test_redirect_to_shop_with_added_product_data()
    {
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'active' => 1,
                    'stock' => $this->faker->numberBetween(1, 100),
                    'is_physical' => 0
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

        $response->assertSessionHas('promo-code', $promoCode);
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
                'locked' => true
            ]
        );

        $newQuantity = 10;
        $response = $this->call(
            'GET',
            '/add-to-cart?products[DLM]=1,year,1'
        );

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
                'products' => [$product['sku'] => $initialQuantity]
            ]
        );

        $newQuantity = 10;
        $response = $this->withSession($this->app['session.store']->all())
            ->call(
            'GET',
            '/add-to-cart?products[DLM]=1,year,1'
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        //assert the old item still exist on session
        $response->assertSessionHas('cartNumberOfItems', 2);

        // assert that the added product exists on session
        $response->assertSessionHas('addedProducts', [0  => $product2]);
    }
}
