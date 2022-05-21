<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class CustomerJsonControllerTest extends EcommerceTestCase
{
    use ArraySubsetAsserts;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_pull_with_term()
    {
        $emailTerm = $this->faker->word;
        $email = $this->faker->word .'.'. $emailTerm . '@gmail.com';

        $customerMatch = $this->fakeCustomer([
            'email' => $email,
            'updated_at' => null,
        ]);

        $billingAddress = $this->fakeAddress(
            [
                'type' => Address::BILLING_ADDRESS_TYPE,
                'customer_id' => $customerMatch['id'],
                'country' => 'US',
                'region' => 'NY',
                'deleted_at' => null,
            ]
        );

        $shippingAddress = $this->fakeAddress(
            [
                'type' => Address::SHIPPING_ADDRESS_TYPE,
                'customer_id' => $customerMatch['id'],
                'country' => 'US',
                'region' => 'NY',
                'deleted_at' => null,
            ]
        );

        $due = $productPrice = $this->faker->randomFloat(2, 50, 90);
        $productQuantity = 1;

        $product = $this->fakeProduct([
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'weight' => 1,
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
            'price' => $productPrice,
            'updated_at' => null,
        ]);

        $brand = 'drumeo';

        $order = $this->fakeOrder(
            [
                'brand' => $brand,
                'user_id' => null,
                'customer_id' => $customerMatch['id'],
                'shipping_address_id' => $shippingAddress['id'],
                'billing_address_id' => $billingAddress['id'],
                'deleted_at' => null,
                'total_due' => $due,
                'product_due' => $due,
                'taxes_due' => 0,
                'shipping_due' => 0,
                'finance_due' => 0,
                'total_paid' => $due,
                'updated_at' => null,
            ]
        );

        $orderItem = $this->fakeOrderItem(
            [
                'order_id' => $order['id'],
                'product_id' => $product['id'],
                'quantity' => $productQuantity,
                'weight' => 0,
                'initial_price' => $product['price'],
                'total_discounted' => 0,
                'final_price' => $product['price'],
                'updated_at' => null,
            ]
        );

        $this->fakeCustomer();
        $this->fakeCustomer();

        $response = $this->call(
            'GET',
            '/customers',
            ['term' => $emailTerm]
        );

        $expected = [
            'data' => [
                [
                    'type' => 'customer',
                    'id' => 1,
                    'attributes' => array_diff_key(
                        $customerMatch,
                        ['id' => true]
                    ),
                ]
            ],
        ];

        $this->assertEquals(200, $response->getStatusCode());

        $decodedResponse = $response->json();

        $this->assertEquals($expected['data'], $decodedResponse['data']);
    }

    public function test_show_existing()
    {
        $customer = $this->fakeCustomer([
            'updated_at' => null,
        ]);

        $response = $this->call('GET', '/customer/' . $customer['id']);

        $this->assertEquals(
            [
                'data' => [
                    'type' => 'customer',
                    'id' => 1,
                    'attributes' => array_diff_key(
                        $customer,
                        ['id' => true]
                    ),
                ]
            ],
            $response->json()
        );
    }

    public function test_show_not_existing()
    {
        $id = $this->faker->numberBetween(1, 1000);

        $response = $this->call('GET', '/customer/' . $id);

        $this->assertEquals(404, $response->getStatusCode());

        // assert not found error
        $this->assertEquals(
            [
                'errors' => [
                    [
                        'detail' => 'Customer not found with id: ' . $id,
                        'title' => 'Not found.',
                    ]
                ],
            ],
            $response->json()
        );
    }

    public function test_update_not_existing()
    {
        $id = $this->faker->numberBetween(1, 1000);

        $response = $this->call(
            'PATCH',
            '/customer/' . $id,
            [
                'note' => $this->faker->word,
            ]
        );

        // assert not found error
        $this->assertEquals(
            [
                'errors' => [
                    [
                        'detail' => 'Update failed, customer not found with id: ' . $id,
                        'title' => 'Not found.',
                    ]
                ],
            ],
            $response->json()
        );
    }

    public function test_update()
    {
        $customer = $this->fakeCustomer([
            'updated_at' => null,
        ]);

        $note = $this->faker->word;

        $response = $this->call(
            'PATCH',
            '/customer/' . $customer['id'],
            [
                
                'data' => [
                    'id' => $customer['id'],
                    'type' => 'customer',
                    'attributes' => [
                        'note' => $note
                    ]
                ],
            ]
        );

        $this->assertEquals(
            [
                'data' => [
                    'type' => 'customer',
                    'id' => 1,
                    'attributes' => array_merge(
                        array_diff_key(
                            $customer,
                            ['id' => true]
                        ),
                        [
                            'note' => $note,
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ]
                    )
                ]
            ],
            $response->json()
        );
    }
}
