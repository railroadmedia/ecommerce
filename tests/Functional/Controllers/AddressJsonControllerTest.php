<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Location\Services\LocationService;

class AddressJsonControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_pull()
    {
        $userId = $this->createAndLogInNewUser();

        $address = $this->fakeAddress(
            [
                'user_id' => $userId
            ]
        );

        $otherUserId = rand();

        $otherAddress = $this->fakeAddress(
            [
                'user_id' => $otherUserId
            ]
        );

        $response = $this->call(
            'GET',
            '/address',
            []
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'type' => 'address',
                        'id' => $address['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $address,
                                [
                                    'id' => true,
                                    'user_id' => true,
                                    'customer_id' => true
                                ]
                            )
                        ),
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

    }

    public function test_pull_multiple_brands()
    {
        $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('can')
            ->willReturn(true);

        $user = $this->fakeUser();

        $brandOne = $this->faker->word;

        $addressOne = $this->fakeAddress(
            [
                'user_id' => $user['id'],
                'brand' => $brandOne
            ]
        );

        $brandTwo = $this->faker->word;

        $addressTwo = $this->fakeAddress(
            [
                'user_id' => $user['id'],
                'brand' => $brandTwo
            ]
        );

        $otherUserId = rand();
        $otherBrand = $this->faker->word;

        $otherAddress = $this->fakeAddress(
            [
                'user_id' => $otherUserId,
                'brand' => $otherBrand
            ]
        );

        $response = $this->call(
            'GET',
            '/address',
            [
                'user_id' => $user['id'],
                'brands' => [$brandOne, $brandTwo]
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'type' => 'address',
                        'id' => $addressOne['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $addressOne,
                                [
                                    'id' => true,
                                    'user_id' => true,
                                    'customer_id' => true
                                ]
                            )
                        ),
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $user['id'],
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'id' => $addressTwo['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $addressTwo,
                                [
                                    'id' => true,
                                    'user_id' => true,
                                    'customer_id' => true
                                ]
                            )
                        ),
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $user['id'],
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $user['id'],
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_store_validation_fails()
    {
        $results = $this->call('PUT', '/address', []);

        //assert the response status code
        $this->assertEquals(422, $results->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.brand',
                    'detail' => 'The brand field is required.',
                    'title' => 'Validation failed.',
                ],
                [
                    'source' => 'data.attributes.type',
                    'detail' => 'The type field is required.',
                    'title' => 'Validation failed.',
                ],
                [
                    'source' => 'data.attributes.country',
                    'detail' => 'The country field is required.',
                    'title' => 'Validation failed.',
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_store_address_invalid_type()
    {
        //call the store method with an invalid type(the valid types are AddressService::SHIPPING_ADDRESS and AddressService::BILLING_ADDRESS)
        $results = $this->call(
            'PUT',
            '/address',
            [
                'data' => [
                    'type' => 'address',
                    'attributes' => [
                        'brand' => $this->faker->word,
                        'type' => $this->faker->word,
                        'user_id' => rand(),
                        'first_name' => $this->faker->firstName,
                        'last_name' => $this->faker->lastName,
                        'street_line_1' => $this->faker->streetAddress,
                        'city' => $this->faker->city,
                        'zip' => $this->faker->postcode,
                        'state' => $this->faker->word,
                        'country' => $this->faker->randomElement(LocationService::countries()),
                    ],
                ],
            ]
        );

        //assert results status code
        $this->assertEquals(422, $results->getStatusCode());

        //assert returned error message
        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.type',
                    'detail' => 'The selected type is invalid.',
                    'title' => 'Validation failed.',
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_store_permissions_fails()
    {
        // current logged in user, not admin
        $userId = $this->createAndLogInNewUser();

        $otherUserId = rand();

        $address = $this->faker->address();

        $response = $this->call(
            'PUT',
            '/address',
            [
                'data' => [
                    'type' => 'address',
                    'attributes' => $address,
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $otherUserId
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'This action is unauthorized, users can only create addresses for themselves.',
            ],
            $response->decodeResponseJson('error')
        );
    }

    public function test_store_response()
    {
        $userId = $this->createAndLogInNewUser();

        $address = $this->faker->address();

        $response = $this->call(
            'PUT',
            '/address',
            [
                'data' => [
                    'type' => 'address',
                    'attributes' => $address,
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(201, $response->getStatusCode());

        // assert that the new created address it's returned in response in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'address',
                    'id' => 1,
                    'attributes' => $address,
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        //assert that the address exists in the database
        $this->assertDatabaseHas(
            'ecommerce_addresses',
            $address + [
                'user_id' => $userId
            ]
        );
    }

    public function test_update_missing_address()
    {
        //take a fake address id
        $randomId = rand();
        $results = $this->call(
            'PATCH',
            '/address/' . $randomId,
            [
                'user_id' => $this->createAndLogInNewUser(),
            ]
        );

        //assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        //assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                [
                    'title' => 'Not found.',
                    'detail' => 'Update failed, address not found with id: ' . $randomId,
                ]
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_user_update_his_address_response()
    {
        $userId = $this->createAndLogInNewUser();

        $address = $this->fakeAddress(['user_id' => $userId]);

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call(
            'PATCH',
            '/address/' . $address['id'],
            [
                'data' => [
                    'id' => $address['id'],
                    'type' => 'address',
                    'attributes' => [
                        'street_line_1' => $newStreetLine1,
                    ],
                ],
            ]
        );

        //assert the response status code
        $this->assertEquals(200, $results->getStatusCode());

        //assert that the updated address  it's returned in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'id' => $address['id'],
                    'type' => 'address',
                    'attributes' => array_merge(
                        array_diff_key(
                            $address,
                            [
                                'id' => true,
                                'user_id' => true
                            ]
                        ),
                        [
                            'street_line_1' => $newStreetLine1,
                            'updated_at' => Carbon::now()
                                ->toDateTimeString()
                        ]
                    ),
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId
                    ]
                ]
            ],
            $results->decodeResponseJson()
        );

        //assert that the address was updated in the database
        $this->assertDatabaseHas(
            'ecommerce_addresses',
            [
                'id' => $address['id'],
                'type' => $address['type'],
                'brand' => $address['brand'],
                'first_name' => $address['first_name'],
                'last_name' => $address['last_name'],
                'street_line_1' => $newStreetLine1,
                'street_line_2' => $address['street_line_2'],
                'city' => $address['city'],
                'zip' => $address['zip'],
                'state' => $address['state'],
                'country' => $address['country'],
                'user_id' => $userId
            ]
        );

        //assert that the old address street line 1 data not exist anymore in the database
        $this->assertDatabaseMissing(
            'ecommerce_addresses',
            [
                'id' => $address['id'],
                'type' => $address['type'],
                'brand' => $address['brand'],
                'first_name' => $address['first_name'],
                'last_name' => $address['last_name'],
                'street_line_1' => $address['street_line_1'],
                'street_line_2' => $address['street_line_2'],
                'city' => $address['city'],
                'zip' => $address['zip'],
                'state' => $address['state'],
                'country' => $address['country'],
                'user_id' => $userId
            ]
        );
    }

    public function test_update_response_unauthorized_user()
    {
        // current logged in user
        $userId = $this->createAndLogInNewUser();

        $otherUser = $this->fakeUser();

        // create an address for a random user
        $address = $this->fakeAddress(['user_id' => $otherUser['id']]);

        $newStreetLine1 = $this->faker->streetAddress;

        $response = $this->call(
            'PATCH',
            '/address/' . $address['id'],
            [
                'data' => [
                    'id' => $address['id'],
                    'type' => 'address',
                    'attributes' => [
                        'street_line_1' => $newStreetLine1,
                    ],
                ],
            ]
        );

        // assert that the logged in user can not update other user's address if it's not admin
        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'This action is unauthorized, only the owning user can update this address.',
            ],
            $response->decodeResponseJson('error')
        );

        // assert that the address was not modified in the database
        $this->assertDatabaseHas(
            'ecommerce_addresses',
            $address
        );
    }

    public function test_delete_unauthorized_user()
    {
        // current logged in user
        $userId = $this->createAndLogInNewUser();

        $otherUser = $this->fakeUser();

        // create an address for a random user
        $address = $this->fakeAddress(['user_id' => $otherUser['id']]);

        $response = $this->call('DELETE', '/address/' . $address['id']);

        // assert that the logged in user have not access if it's unauthorized
        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'This action is unauthorized.'
            ],
            $response->decodeResponseJson('error')
        );

        // assert that the address was not deleted from the database
        $this->assertDatabaseHas('ecommerce_addresses', $address);
    }

    public function test_user_delete_his_address()
    {
        // create an address for logged in user
        $userId = $this->createAndLogInNewUser();

        $address = $this->fakeAddress(['user_id' => $userId]);

        $response = $this->call('DELETE', '/address/' . $address['id']);

        // assert the response status code
        $this->assertEquals(204, $response->getStatusCode());

        // assert that the address was deleted
        $this->assertSoftDeleted(
            'ecommerce_addresses',
            $address
        );
    }

    public function test_delete_address_with_orders()
    {
        $userId = $this->createAndLogInNewUser();

        $address = $this->fakeAddress(['user_id' => $userId]);

        $order = $this->fakeOrder(
            [
                'user_id' => $userId,
                'shipping_address_id' => $address['id']
            ]
        );

        $results = $this->call('DELETE', '/address/' . $address['id']);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'Delete failed, orders found with selected address.'
            ],
            $results->decodeResponseJson('error')
        );
    }

    public function test_create_address_with_invalid_country()
    {
        $userId = $this->createAndLogInNewUser();
        $country = $this->faker->word;
        $type = $this->faker->randomElement(
            [
                Address::SHIPPING_ADDRESS_TYPE,
                Address::BILLING_ADDRESS_TYPE,
            ]
        );
        $userId = rand();
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $streetLine1 = $this->faker->streetAddress;
        $city = $this->faker->city;
        $zip = $this->faker->postcode;
        $state = $this->faker->word;

        $results = $this->call(
            'PUT',
            '/address',
            [
                'data' => [
                    'type' => 'address',
                    'attributes' => [
                        'brand' => 'drumeo',
                        'type' => $type,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'street_line_1' => $streetLine1,
                        'city' => $city,
                        'zip' => $zip,
                        'state' => $state,
                        'country' => $country,
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ]
                    ]
                ],
            ]
        );

        //assert the response status code
        $this->assertEquals(422, $results->getStatusCode());

        //assert the error message
        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.country',
                    'detail' => 'The selected country is invalid.',
                    'title' => 'Validation failed.',
                ]
            ],
            $results->decodeResponseJson('errors')
        );

        //assert that the address with invalid country was not created
        $this->assertDatabaseMissing(
            'ecommerce_addresses',
            [
                'type' => $type,
                'user_id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'street_line_1' => $streetLine1,
                'city' => $city,
                'zip' => $zip,
                'state' => $state,
                'country' => $country,
            ]
        );
    }

    public function test_update_address_with_invalid_country()
    {
        $country = $this->faker->word;
        $userId = $this->createAndLogInNewUser();

        $address = $this->fakeAddress(['user_id' => $userId]);

        $results = $this->call(
            'PATCH',
            '/address/' . $address['id'],
            [
                'data' => [
                    'type' => 'address',
                    'attributes' => [
                        'country' => $country,
                        'user_id' => $userId,
                    ],
                ],
            ]
        );

        //assert response status code
        $this->assertEquals(422, $results->getStatusCode());

        //assert response error message
        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.country',
                    'detail' => 'The selected country is invalid.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );

        //assert that the address was not modified in the database
        $this->assertDatabaseHas('ecommerce_addresses', $address);
        $this->assertDatabaseMissing(
            'ecommerce_addresses',
            [
                'id' => $address['id'],
                'country' => $country,
            ]
        );
    }

    public function test_admin_store_user_address()
    {
        //mock permission
        $this->permissionServiceMock->method('canOrThrow')
            ->willReturn(true);
        $this->permissionServiceMock->method('can')
            ->willReturn(true);

        $user = $this->fakeUser();

        $address = $this->faker->address();

        $response = $this->call(
            'PUT',
            '/address',
            [
                'data' => [
                    'type' => 'address',
                    'attributes' => $address,
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $user['id'],
                            ]
                        ]
                    ]
                ],
            ]
        );

        //assert the response status code
        $this->assertEquals(201, $response->getStatusCode());

        // assert that the new created address it's returned in response in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'address',
                    'attributes' => $address,
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $user['id'],
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $user['id'],
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        //assert that the address exists in the database
        $this->assertDatabaseHas(
            'ecommerce_addresses',
            $address + ['user_id' => $user['id']]
        );
    }

    public function test_admin_update_user_address()
    {
        $this->permissionServiceMock->method('canOrThrow')
            ->willReturn(true);
        $this->permissionServiceMock->method('can')
            ->willReturn(true);

        $user = $this->fakeUser();

        $address = $this->fakeAddress(['user_id' => $user['id']]);

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call(
            'PATCH',
            '/address/' . $address['id'],
            [
                'data' => [
                    'id' => $address['id'],
                    'type' => 'address',
                    'attributes' => [
                        'street_line_1' => $newStreetLine1,
                    ],
                ],
            ]
        );

        //assert the response status code
        $this->assertEquals(200, $results->getStatusCode());

        //assert that the updated address  it's returned in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'id' => $address['id'],
                    'type' => 'address',
                    'attributes' => array_merge(
                        array_diff_key(
                            $address,
                            [
                                'id' => true,
                                'user_id' => true
                            ]
                        ),
                        [
                            'street_line_1' => $newStreetLine1,
                            'updated_at' => Carbon::now()
                        ]
                    ),
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $user['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $user['id']
                    ]
                ]
            ],
            $results->decodeResponseJson()
        );

        //assert that the address was updated in the database
        $this->assertDatabaseHas(
            'ecommerce_addresses',
            [
                'id' => $address['id'],
                'type' => $address['type'],
                'brand' => $address['brand'],
                'first_name' => $address['first_name'],
                'last_name' => $address['last_name'],
                'street_line_1' => $newStreetLine1,
                'street_line_2' => $address['street_line_2'],
                'city' => $address['city'],
                'zip' => $address['zip'],
                'state' => $address['state'],
                'country' => $address['country'],
                'user_id' => $user['id']
            ]
        );

        //assert that the old address street line 1 data not exist anymore in the database
        $this->assertDatabaseMissing(
            'ecommerce_addresses',
            [
                'id' => $address['id'],
                'type' => $address['type'],
                'brand' => $address['brand'],
                'first_name' => $address['first_name'],
                'last_name' => $address['last_name'],
                'street_line_1' => $address['street_line_1'],
                'street_line_2' => $address['street_line_2'],
                'city' => $address['city'],
                'zip' => $address['zip'],
                'state' => $address['state'],
                'country' => $address['country'],
                'user_id' => $user['id']
            ]
        );
    }

    public function test_admin_delete_user_address()
    {
        $this->permissionServiceMock->method('canOrThrow')
            ->willReturn(true);

        $userId = rand();

        $address = $this->fakeAddress(['user_id' => $userId]);

        $results = $this->call('DELETE', '/address/' . $address['id']);

        $this->assertEquals(204, $results->getStatusCode());

        // assert that address was deleted from database
        $this->assertSoftDeleted('ecommerce_addresses', $address);
    }

    public function test_customer_create_address()
    {
        $customer = $this->fakeCustomer();

        $address = $this->faker->address();

        $results = $this->call(
            'PUT',
            '/address',
            [
                'data' => [
                    'type' => 'address',
                    'attributes' => $address,
                    'relationships' => [
                        'customer' => [
                            'data' => [
                                'type' => 'customer',
                                'id' => $customer['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(201, $results->getStatusCode());

        // assert that the new created address it's returned in response in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'address',
                    'id' => 1,
                    'attributes' => $address,
                    'relationships' => [
                        'customer' => [
                            'data' => [
                                'type' => 'customer',
                                'id' => $customer['id']
                            ]
                        ]
                    ]
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert that the address exists in the database
        $this->assertDatabaseHas(
            'ecommerce_addresses',
            $address + [
                'customer_id' => $customer['id']
            ]
        );
    }

    public function test_customer_update_his_address()
    {
        $customer = $this->fakeCustomer();
        $address = $this->fakeAddress(['customer_id' => $customer['id']]);

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call(
            'PATCH',
            '/address/' . $address['id'],
            [
                'data' => [
                    'id' => $address['id'],
                    'type' => 'address',
                    'attributes' => [
                        'street_line_1' => $newStreetLine1,
                    ],
                    'relationships' => [
                        'customer' => [
                            'data' => [
                                'type' => 'customer',
                                'id' => $customer['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'id' => $address['id'],
                    'type' => 'address',
                    'attributes' => array_merge(
                        array_diff_key(
                            $address,
                            [
                                'id' => true,
                                'customer_id' => true
                            ]
                        ),
                        [
                            'street_line_1' => $newStreetLine1,
                            'updated_at' => Carbon::now()
                                ->toDateTimeString()
                        ]
                    ),
                    'relationships' => [
                        'customer' => [
                            'data' => [
                                'type' => 'customer',
                                'id' => $customer['id']
                            ]
                        ]
                    ]
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert address row was updated in the database
        $this->assertDatabaseHas(
            'ecommerce_addresses',
            [
                'id' => $address['id'],
                'street_line_1' => $newStreetLine1,
                'customer_id' => $customer['id'],
                'updated_at' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        // assert that the old address street line 1 data not exist anymore in the database
        $this->assertDatabaseMissing(
            'ecommerce_addresses',
            [
                'id' => $address['id'],
                'customer_id' => $customer['id'],
                'street_line_1' => $address['street_line_1'],
            ]
        );
    }

    public function test_customer_restriction_on_update_other_address()
    {
        $customer = $this->fakeCustomer();
        $address = $this->fakeAddress(['customer_id' => rand()]);

        $newStreetLine1 = $this->faker->streetAddress;

        $response = $this->call(
            'PATCH',
            '/address/' . $address['id'],
            [
                'data' => [
                    'id' => $address['id'],
                    'type' => 'address',
                    'attributes' => [
                        'street_line_1' => $newStreetLine1,
                    ],
                    'relationships' => [
                        'customer' => [
                            'data' => [
                                'type' => 'customer',
                                'id' => $customer['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        //assert response
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'This action is unauthorized. You must pass the correct customer id.'
            ],
            $response->decodeResponseJson('error')
        );

        //assert database content
        $this->assertDatabaseMissing(
            'ecommerce_addresses',
            [
                'id' => $address['id'],
                'street_line_1' => $newStreetLine1,
            ]
        );
    }

    public function test_customer_delete_his_address()
    {
        $customer = $this->fakeCustomer();

        $address = $this->fakeAddress(['customer_id' => $customer['id']]);

        $results = $this->call(
            'DELETE',
            '/address/' . $address['id'],
            [
                'customer_id' => $customer['id']
            ]
        );

        //assert response
        $this->assertEquals(204, $results->getStatusCode());

        //assert database content
        $this->assertSoftDeleted('ecommerce_addresses', $address);
    }

    public function test_customer_can_not_delete_others_address()
    {
        $customerRequest = $this->fakeCustomer();
        $customerAddress = $this->fakeCustomer();

        $address = $this->fakeAddress(['customer_id' => $customerAddress['id']]);

        $response = $this->call(
            'DELETE',
            '/address/' . $address['id'],
            [
                'customer_id' => $customerRequest['id']
            ]
        );

        // assert response
        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'This action is unauthorized.',
            ],
            $response->decodeResponseJson('error')
        );

        // assert database
        $this->assertDatabaseHas('ecommerce_addresses', $address);
    }
}
