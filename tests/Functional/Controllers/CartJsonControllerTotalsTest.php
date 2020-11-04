<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Location\Services\ConfigService;

class CartJsonControllerTotalsTest extends EcommerceTestCase
{
    /**
     * @var Store
     */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->app->make(Store::class);

        $this->addRecommendedProducts();
    }

    public function test_taxes()
    {
        // canada BC IP, the billing address will be set automatically
        ConfigService::$testingIP = "70.69.219.138";

        $this->session->flush();

        $product = $this->fakeProduct(
            [
                'price' => 12.95,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 0,
                'type' => Product::TYPE_DIGITAL_ONE_TIME,
                'subscription_interval_type' => null,
                'subscription_interval_count' => null,
                'weight' => 0,
            ]
        );

        $productPhysical = $this->fakeProduct(
            [
                'price' => 12.95,
                'active' => 1,
                'description' => $this->faker->word,
                'is_physical' => 1,
                'type' => Product::TYPE_PHYSICAL_ONE_TIME,
                'subscription_interval_type' => null,
                'subscription_interval_count' => null,
                'weight' => 2,
            ]
        );

        $initialQuantity = 2;

        // add the item
        $response = $this->call(
            'PUT',
            '/json/add-to-cart/',
            [
                'products' => [$product['sku'] => $initialQuantity],
            ]
        );
        $jsonResponse = $response->decodeResponseJson();

        $this->assertNull($jsonResponse["meta"]["cart"]["shipping_address"]);

        // the country will be auto set from the test IP location
        $this->assertNotEmpty($jsonResponse["meta"]["cart"]["billing_address"]['country']);

        $this->assertEquals(3.11, $jsonResponse["meta"]["cart"]["totals"]["tax"]);

        // set the shipping address, since there are no physical items tax should still be set
        $shippingAddress = [
            'shipping_country' => 'United States',
            'shipping_region' => 'Ohio',
        ];

        $response = $this->call('PUT', '/session/address', $shippingAddress);
        $jsonResponse = $response->decodeResponseJson();

        $this->assertEquals(3.11, $jsonResponse["meta"]["cart"]["totals"]["tax"]);

        // now add a physical item and taxes should be 0
        $response = $this->call(
            'PUT',
            '/json/add-to-cart/',
            [
                'products' => [$productPhysical['sku'] => $initialQuantity],
            ]
        );
        $jsonResponse = $response->decodeResponseJson();

        $this->assertEquals(0, $jsonResponse["meta"]["cart"]["totals"]["tax"]);

        // remove the physical item and tax should come back
        $response = $this->call(
            'DELETE',
            '/json/remove-from-cart/' . $productPhysical['sku']
        );
        $jsonResponse = $response->decodeResponseJson();

        $this->assertEquals(3.11, $jsonResponse["meta"]["cart"]["totals"]["tax"]);

        // now change the billing address so there is no more tax
        $billingAddress= [
            'billing_country' => 'United States',
            'billing_region' => 'Ohio',
        ];

        $response = $this->call('PUT', '/session/address', $billingAddress);
        $jsonResponse = $response->decodeResponseJson();

        $this->assertEquals(0, $jsonResponse["meta"]["cart"]["totals"]["tax"]);

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'discounts',
                        'shipping_address',
                        'billing_address',
                        'number_of_payments',
                        'totals' => [
                            'shipping',
                            'tax',
                            'due'
                        ]
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection count
        $this->assertEquals(1, count($decodedResponse['meta']['cart']['items']));

        // assert cart item data
        $this->assertEquals(
            [
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $initialQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $initialQuantity,
                'price_after_discounts' => $product['price'] * $initialQuantity,
                'requires_shipping' => false,
                'is_digital' => !$product['is_physical'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $totalDue = $product['price'] * $initialQuantity;

        // assert total due
        $this->assertEquals(
            $totalDue,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(1, count($cart->getItems()));

        // assert cart item
        $cartItemOne = $cart->getItemBySku($product['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($initialQuantity, $cartItemOne->getQuantity());
    }
}
