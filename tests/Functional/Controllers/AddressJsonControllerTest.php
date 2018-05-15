<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\AddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Webpatser\Countries\Countries;

class AddressJsonControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_store_validation()
    {
        $results = $this->call('PUT', '/address', []);

        //check the response status code
        $this->assertEquals(422, $results->getStatusCode());

        //check that all the validation errors are returned
        $this->assertEquals([
            [
                "source" => "type",
                "detail" => "The type field is required.",
            ],
            [
                "source" => "city",
                "detail" => "The city field is required.",
            ],
            [
                "source" => "zip",
                "detail" => "The zip field is required.",
            ],
            [
                "source" => "state",
                "detail" => "The state field is required."
            ],
            [
                "source" => "country",
                "detail" => "The country field is required."
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store_address_invalid_type()
    {
        //call the store method with an invalid type(the valid types are AddressService::SHIPPING_ADDRESS and AddressService::BILLING_ADDRESS)
        $results = $this->call('PUT', '/address', [
            'type'          => $this->faker->word,
            'user_id'       => rand(),
            'first_name'    => $this->faker->firstName,
            'last_name'     => $this->faker->lastName,
            'street_line_1' => $this->faker->streetAddress,
            'city'          => $this->faker->city,
            'zip'           => $this->faker->postcode,
            'state'         => $this->faker->word,
            'country'       => $this->faker->randomElement(array_column(Countries::getCountries(), 'full_name'))
        ]);

        //check results status code
        $this->assertEquals(422, $results->getStatusCode());

        //check returned error message
        $this->assertEquals([
            [
                "source" => "type",
                "detail" => "The selected type is invalid.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store_response()
    {
        $type        = $this->faker->randomElement([
            AddressService::SHIPPING_ADDRESS,
            AddressService::BILLING_ADDRESS
        ]);
        $userId      = rand();
        $firstName   = $this->faker->firstName;
        $lastName    = $this->faker->lastName;
        $streetLine1 = $this->faker->streetAddress;
        $city        = $this->faker->city;
        $zip         = $this->faker->postcode;
        $state       = $this->faker->word;
        $country     = $this->faker->randomElement(array_column(Countries::getCountries(), 'full_name'));

        $results = $this->call('PUT', '/address', [
            'type'          => $type,
            'user_id'       => $userId,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'street_line_1' => $streetLine1,
            'city'          => $city,
            'zip'           => $zip,
            'state'         => $state,
            'country'       => $country
        ]);

        //check the response status code
        $this->assertEquals(200, $results->getStatusCode());

        //check that the new created address it's returned in response in JSON format
        $this->assertEquals([
            'id'            => 1,
            'type'          => $type,
            'brand'         => ConfigService::$brand,
            'user_id'       => $userId,
            'customer_id'   => null,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'street_line_1' => $streetLine1,
            'street_line_2' => null,
            'city'          => $city,
            'zip'           => $zip,
            'state'         => $state,
            'country'       => $country,
            'created_on'    => Carbon::now()->toDateTimeString(),
            'updated_on'    => null
        ], $results->decodeResponseJson()['results']);

        //check that the address exists in the database
        $this->assertDatabaseHas(ConfigService::$tableAddress,
            [
                'type'          => $type,
                'brand'         => ConfigService::$brand,
                'user_id'       => $userId,
                'customer_id'   => null,
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'street_line_1' => $streetLine1,
                'street_line_2' => null,
                'city'          => $city,
                'zip'           => $zip,
                'state'         => $state,
                'country'       => $country,
                'created_on'    => Carbon::now()->toDateTimeString(),
                'updated_on'    => null
            ]);
    }

    public function test_update_missing_address()
    {
        //take a fake address id
        $randomId = rand();
        $userId   = $this->createAndLogInNewUser();
        $results  = $this->call('PATCH', '/address/' . $randomId,
            [
                'user_id' => $userId
            ]);

        //check response status code
        $this->assertEquals(404, $results->getStatusCode());

        //check the error message that it's returned in JSON format
        $this->assertEquals(
            [
                "title"  => "Not found.",
                "detail" => "Update failed, address not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_user_update_his_address_response()
    {
        //create user address
        $userId        = $this->createAndLogInNewUser();
        $address       = $this->faker->address(['type' => AddressService::SHIPPING_ADDRESS, 'user_id' => $userId]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'user_id'       => $userId,
            'street_line_1' => $newStreetLine1,
        ]);

        //check the response status code
        $this->assertEquals(201, $results->getStatusCode());

        //check that the updated address  it's returned in JSON format
        $this->assertEquals([
            'id'            => $address['id'],
            'type'          => $address['type'],
            'brand'         => $address['brand'],
            'user_id'       => $address['user_id'],
            'customer_id'   => $address['customer_id'],
            'first_name'    => $address['first_name'],
            'last_name'     => $address['last_name'],
            'street_line_1' => $newStreetLine1,
            'street_line_2' => $address['street_line_2'],
            'city'          => $address['city'],
            'zip'           => $address['zip'],
            'state'         => $address['state'],
            'country'       => $address['country'],
            'created_on'    => $address['created_on'],
            'updated_on'    => Carbon::now()->toDateTimeString()
        ], $results->decodeResponseJson()['results']);

        //check that the address was updated in the database
        $this->assertDatabaseHas(ConfigService::$tableAddress, [
            'id'            => $address['id'],
            'type'          => $address['type'],
            'brand'         => $address['brand'],
            'user_id'       => $address['user_id'],
            'customer_id'   => $address['customer_id'],
            'first_name'    => $address['first_name'],
            'last_name'     => $address['last_name'],
            'street_line_1' => $newStreetLine1,
            'street_line_2' => $address['street_line_2'],
            'city'          => $address['city'],
            'zip'           => $address['zip'],
            'state'         => $address['state'],
            'country'       => $address['country'],
            'created_on'    => $address['created_on'],
        ]);

        //check that the old address street line 1 data not exist anymore in the database
        $this->assertDatabaseMissing(ConfigService::$tableAddress, [
            'id'            => $address['id'],
            'type'          => $address['type'],
            'brand'         => $address['brand'],
            'user_id'       => $address['user_id'],
            'customer_id'   => $address['customer_id'],
            'first_name'    => $address['first_name'],
            'last_name'     => $address['last_name'],
            'street_line_1' => $address['street_line_1'],
            'street_line_2' => $address['street_line_2'],
            'city'          => $address['city'],
            'zip'           => $address['zip'],
            'state'         => $address['state'],
            'country'       => $address['country'],
            'created_on'    => $address['created_on'],
        ]);
    }

    public function test_update_response_unauthorized_user()
    {
        $userId = $this->createAndLogInNewUser();

        //create an address for a random user
        $address       = $this->faker->address([
            'type'    => AddressService::SHIPPING_ADDRESS,
            'user_id' => rand()
        ]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'user_id'       => $userId,
            'street_line_1' => $newStreetLine1,
        ]);

        //check that the logged in user can not update other user's address if it's not admin
        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals([
            'title'  => 'Not allowed.',
            'detail' => 'This action is unauthorized.'
        ], $results->decodeResponseJson()['error']);

        //check that the address was not modified in the database
        $this->assertDatabaseHas(ConfigService::$tableAddress, $address);
    }

    public function test_delete_unauthorized_user()
    {
        //create an address for a random user
        $address       = $this->faker->address([
            'type'    => AddressService::SHIPPING_ADDRESS,
            'user_id' => rand()
        ]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $results = $this->call('DELETE', '/address/' . $address['id']);

        //check that the logged in user have not access if it's unauthorized
        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);

        //check that the address was not deleted from the database
        $this->assertDatabaseHas(ConfigService::$tableAddress, $address);
    }

    public function test_user_delete_his_address()
    {
        //create an address for logged in user
        $userId        = $this->createAndLogInNewUser();
        $address       = $this->faker->address([
            'type'    => AddressService::SHIPPING_ADDRESS,
            'user_id' => $userId
        ]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $results = $this->call('DELETE', '/address/' . $address['id']);

        //check the response status code
        $this->assertEquals(204, $results->getStatusCode());

        //check that the address was deleted
        $this->assertDatabaseMissing(ConfigService::$tableAddress, $address);
    }

    public function _test_delete_address_with_orders()
    {
        $userId        = $this->createAndLogInNewUser();
        $address       = $this->faker->address([
            'type'    => AddressService::SHIPPING_ADDRESS,
            'user_id' => $userId
        ]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);
        $order         = [
            'uuid'                => $this->faker->uuid,
            'due'                 => $this->faker->numberBetween(0, 1000),
            'tax'                 => rand(),
            'shipping_costs'      => rand(),
            'paid'                => rand(),
            'user_id'             => $address['user_id'],
            'customer_id'         => $address['customer_id'],
            'brand'               => ConfigService::$brand,
            'shipping_address_id' => $address['id'],
            'billing_address_id'  => null,
            'created_on'          => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableOrder)->insertGetId($order);

        $results = $this->call('DELETE', '/address/' . $address['id']);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "Delete failed, exists orders defined for the selected address.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_create_address_with_invalid_country()
    {
        $country     = $this->faker->word;
        $type        = $this->faker->randomElement([
            AddressService::SHIPPING_ADDRESS,
            AddressService::BILLING_ADDRESS
        ]);
        $userId      = rand();
        $firstName   = $this->faker->firstName;
        $lastName    = $this->faker->lastName;
        $streetLine1 = $this->faker->streetAddress;
        $city        = $this->faker->city;
        $zip         = $this->faker->postcode;
        $state       = $this->faker->word;

        $results = $this->call('PUT', '/address', [
            'type'          => $type,
            'user_id'       => $userId,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'street_line_1' => $streetLine1,
            'city'          => $city,
            'zip'           => $zip,
            'state'         => $state,
            'country'       => $country
        ]);

        //check the response status code
        $this->assertEquals(422, $results->getStatusCode());

        //check the error message
        $this->assertEquals([
            [
                "source" => "country",
                "detail" => "The country field it's invalid."
            ]
        ], $results->decodeResponseJson()['errors']);

        //check that the address with invalid country was not created
        $this->assertDatabaseMissing(ConfigService::$tableAddress,
            [
                'type'          => $type,
                'user_id'       => $userId,
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'street_line_1' => $streetLine1,
                'city'          => $city,
                'zip'           => $zip,
                'state'         => $state,
                'country'       => $country
            ]);
    }

    public function test_update_address_with_invalid_country()
    {
        $country       = $this->faker->word;
        $userId        = $this->createAndLogInNewUser();
        $address       = $this->faker->address([
            'type'    => AddressService::SHIPPING_ADDRESS,
            'user_id' => $userId
        ]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'country' => $country,
            'user_id' => $userId
        ]);

        //check response status code
        $this->assertEquals(422, $results->getStatusCode());

        //check response error message
        $this->assertEquals([
            [
                "source" => "country",
                "detail" => "The country field it's invalid."
            ]
        ], $results->decodeResponseJson()['errors']);

        //check that the address was not modified in the database
        $this->assertDatabaseHas(ConfigService::$tableAddress, $address);
        $this->assertDatabaseMissing(ConfigService::$tableAddress,
            [
                'id'      => $address['id'],
                'country' => $country
            ]
        );
    }

    public function test_admin_store_user_address()
    {
        //mock permission
        $this->permissionServiceMock->method('is')->willReturn(true);

        $type = $this->faker->randomElement([
            AddressService::SHIPPING_ADDRESS,
            AddressService::BILLING_ADDRESS
        ]);

        $randomUserId = rand();
        $firstName    = $this->faker->firstName;
        $lastName     = $this->faker->lastName;
        $streetLine1  = $this->faker->streetAddress;
        $city         = $this->faker->city;
        $zip          = $this->faker->postcode;
        $state        = $this->faker->word;
        $country      = $this->faker->randomElement(array_column(Countries::getCountries(), 'full_name'));

        $results = $this->call('PUT', '/address', [
            'type'          => $type,
            'user_id'       => $randomUserId,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'street_line_1' => $streetLine1,
            'city'          => $city,
            'zip'           => $zip,
            'state'         => $state,
            'country'       => $country
        ]);

        // check response status code
        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals([
            'id'            => 1,
            'type'          => $type,
            'brand'         => ConfigService::$brand,
            'user_id'       => $randomUserId,
            'customer_id'   => null,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'street_line_1' => $streetLine1,
            'street_line_2' => null,
            'city'          => $city,
            'zip'           => $zip,
            'state'         => $state,
            'country'       => $country,
            'created_on'    => Carbon::now()->toDateTimeString(),
            'updated_on'    => null
        ], $results->decodeResponseJson()['results']);

        //check address was saved in the database
        $this->assertDatabaseHas(ConfigService::$tableAddress,[
            'id'            => 1,
            'type'          => $type,
            'brand'         => ConfigService::$brand,
            'user_id'       => $randomUserId,
            'customer_id'   => null,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'street_line_1' => $streetLine1,
            'street_line_2' => null,
            'city'          => $city,
            'zip'           => $zip,
            'state'         => $state,
            'country'       => $country,
            'created_on'    => Carbon::now()->toDateTimeString(),
            'updated_on'    => null
        ]);
    }

    public function test_admin_update_user_address()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $address       = $this->faker->address([
            'type'    => AddressService::SHIPPING_ADDRESS,
            'user_id' => rand()
        ]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'user_id'       => rand(),
            'street_line_1' => $newStreetLine1
        ]);

        //check the response status code
        $this->assertEquals(201, $results->getStatusCode());

        //check that the updated addess it's returned in JSON format
        $this->assertEquals([
            'id'            => $address['id'],
            'type'          => $address['type'],
            'brand'         => $address['brand'],
            'user_id'       => $address['user_id'],
            'customer_id'   => $address['customer_id'],
            'first_name'    => $address['first_name'],
            'last_name'     => $address['last_name'],
            'street_line_1' => $newStreetLine1,
            'street_line_2' => $address['street_line_2'],
            'city'          => $address['city'],
            'zip'           => $address['zip'],
            'state'         => $address['state'],
            'country'       => $address['country'],
            'created_on'    => $address['created_on'],
            'updated_on'    => Carbon::now()->toDateTimeString()
        ], $results->decodeResponseJson()['results']);

        //check that the address was updated in the database
        $this->assertDatabaseHas(ConfigService::$tableAddress,[
            'id'            => $address['id'],
            'type'          => $address['type'],
            'brand'         => $address['brand'],
            'user_id'       => $address['user_id'],
            'customer_id'   => $address['customer_id'],
            'first_name'    => $address['first_name'],
            'last_name'     => $address['last_name'],
            'street_line_1' => $newStreetLine1,
            'street_line_2' => $address['street_line_2'],
            'city'          => $address['city'],
            'zip'           => $address['zip'],
            'state'         => $address['state'],
            'country'       => $address['country'],
            'created_on'    => $address['created_on'],
            'updated_on'    => Carbon::now()->toDateTimeString()
        ]);
    }

    public function test_admin_delete_user_address()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $address       = $this->faker->address([
            'type'    => AddressService::SHIPPING_ADDRESS,
            'user_id' => rand()
        ]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $results = $this->call('DELETE', '/address/' . $address['id']);

        $this->assertEquals(204, $results->getStatusCode());
    }

    public function test_customer_create_address()
    {
        $customer   = $this->faker->customer();
        $customerId = $this->databaseManager->table(ConfigService::$tableCustomer)->insertGetId($customer);

        $type        = $this->faker->randomElement([
            AddressService::SHIPPING_ADDRESS,
            AddressService::BILLING_ADDRESS
        ]);
        $firstName   = $this->faker->firstName;
        $lastName    = $this->faker->lastName;
        $streetLine1 = $this->faker->streetAddress;
        $city        = $this->faker->city;
        $zip         = $this->faker->postcode;
        $state       = $this->faker->word;
        $country     = $this->faker->randomElement(array_column(Countries::getCountries(), 'full_name'));

        $results = $this->call('PUT', '/address', [
            'type'          => $type,
            'customer_id'   => $customerId,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'street_line_1' => $streetLine1,
            'city'          => $city,
            'zip'           => $zip,
            'state'         => $state,
            'country'       => $country
        ]);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals([
            'id'            => 1,
            'type'          => $type,
            'brand'         => ConfigService::$brand,
            'user_id'       => null,
            'customer_id'   => $customerId,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'street_line_1' => $streetLine1,
            'street_line_2' => null,
            'city'          => $city,
            'zip'           => $zip,
            'state'         => $state,
            'country'       => $country,
            'created_on'    => Carbon::now()->toDateTimeString(),
            'updated_on'    => null
        ], $results->decodeResponseJson()['results']);
    }

    public function test_customer_update_his_address()
    {
        $customer       = $this->faker->customer();
        $customer['id'] = $this->databaseManager->table(ConfigService::$tableCustomer)->insertGetId($customer);

        $address       = $this->faker->address([
            'type'        => AddressService::SHIPPING_ADDRESS,
            'customer_id' => $customer['id']
        ]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'customer_id'   => $customer['id'],
            'street_line_1' => $newStreetLine1
        ]);

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals([
            'id'            => $address['id'],
            'type'          => $address['type'],
            'brand'         => $address['brand'],
            'user_id'       => $address['user_id'],
            'customer_id'   => $address['customer_id'],
            'first_name'    => $address['first_name'],
            'last_name'     => $address['last_name'],
            'street_line_1' => $newStreetLine1,
            'street_line_2' => $address['street_line_2'],
            'city'          => $address['city'],
            'zip'           => $address['zip'],
            'state'         => $address['state'],
            'country'       => $address['country'],
            'created_on'    => $address['created_on'],
            'updated_on'    => Carbon::now()->toDateTimeString()
        ], $results->decodeResponseJson()['results']);
    }

    public function test_customer_restriction_on_update_other_address()
    {
        $customer       = $this->faker->customer();
        $customer['id'] = $this->databaseManager->table(ConfigService::$tableCustomer)->insertGetId($customer);

        $address       = $this->faker->address(['user_id' => rand()]);
        $address['id'] = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'customer_id'   => $customer['id'],
            'street_line_1' => $newStreetLine1
        ]);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_customer_delete_his_address()
    {
        $customer       = $this->faker->customer();
        $customer['id'] = $this->databaseManager->table(ConfigService::$tableCustomer)->insertGetId($customer);
        $address        = $this->faker->address(['customer_id' => $customer['id']]);
        $address['id']  = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);

        $results = $this->call('DELETE', '/address/' . $address['id'], [
            'customer_id' => $customer['id']
        ]);

        $this->assertEquals(204, $results->getStatusCode());
    }

    public function test_customer_can_not_delete_others_address()
    {
        $customer       = $this->faker->customer();
        $customer['id'] = $this->databaseManager->table(ConfigService::$tableCustomer)->insertGetId($customer);
        $address        = $this->faker->address(['user_id' => rand()]);
        $address['id']  = $this->databaseManager->table(ConfigService::$tableAddress)->insertGetId($address);
        $results        = $this->call('DELETE', '/address/' . $address['id'], [
            'customer_id' => $customer['id']
        ]);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }
}
