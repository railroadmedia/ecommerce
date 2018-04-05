<?php


namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\AddressFactory;
use Railroad\Ecommerce\Factories\CustomerFactory;
use Railroad\Ecommerce\Services\AddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Webpatser\Countries\Countries;


class AddressJsonControllerTest extends EcommerceTestCase
{

    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->addressFactory = $this->app->make(AddressFactory::class);
        $this->customerFactory = $this->app->make(CustomerFactory::class);
    }

    public function test_store_validation()
    {
        $results = $this->call('PUT', '/address', []);
        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "type",
                "detail" => "The type field is required.",
            ],
            [
                "source" => "first_name",
                "detail" => "The first name field is required.",
            ],
            [
                "source" => "last_name",
                "detail" => "The last name field is required.",
            ],
            [
                "source" => "street_line_1",
                "detail" => "The street line 1 field is required.",
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
        $results = $this->call('PUT', '/address', [
            'type' => $this->faker->word,
            'user_id' => rand(),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'street_line_1' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'state' => $this->faker->word,
            'country' => $this->faker->randomElement(array_column(Countries::getCountries(), 'full_name'))
        ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "type",
                "detail" => "The selected type is invalid.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store_response()
    {
        $type = $this->faker->randomElement([
            AddressService::SHIPPING_ADDRESS,
            AddressService::BILLING_ADDRESS
        ]);
        $userId = rand();
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $streetLine1 = $this->faker->streetAddress;
        $city = $this->faker->city;
        $zip = $this->faker->postcode;
        $state = $this->faker->word;
        $country = $this->faker->randomElement(array_column(Countries::getCountries(), 'full_name'));

        $results = $this->call('PUT', '/address', [
            'type' => $type,
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'street_line_1' => $streetLine1,
            'city' => $city,
            'zip' => $zip,
            'state' => $state,
            'country' => $country
        ]);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals([
            'id' => 1,
            'type' => $type,
            'brand' => ConfigService::$brand,
            'user_id' => $userId,
            'customer_id' => null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'street_line_1' => $streetLine1,
            'street_line_2' => null,
            'city' => $city,
            'zip' => $zip,
            'state' => $state,
            'country' => $country,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ], $results->decodeResponseJson()['results']);
    }

    public function test_update_missing_address()
    {
        $randomId = rand();
        $userId = $this->createAndLogInNewUser();
        $results = $this->call('PATCH', '/address/' . $randomId,
            [
                'user_id' => $userId
            ]);


        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_user_update_his_address_response()
    {
        $userId = $this->createAndLogInNewUser();

        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, $userId);

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'user_id' => $userId,
            'street_line_1' => $newStreetLine1,
        ]);

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals([
            'id' => $address['id'],
            'type' => $address['type'],
            'brand' => $address['brand'],
            'user_id' => $address['user_id'],
            'customer_id' => $address['customer_id'],
            'first_name' => $address['first_name'],
            'last_name' => $address['last_name'],
            'street_line_1' => $newStreetLine1,
            'street_line_2' => $address['street_line_2'],
            'city' => $address['city'],
            'zip' => $address['zip'],
            'state' => $address['state'],
            'country' => $address['country'],
            'created_on' => $address['created_on'],
            'updated_on' => Carbon::now()->toDateTimeString()
        ], $results->decodeResponseJson()['results']);
    }

    public function test_update_response_unauthorized_user()
    {
        $userId = $this->createAndLogInNewUser();

        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, rand());

        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'user_id' => $userId,
            'street_line_1' => $newStreetLine1,
        ]);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals([
            'title' => 'Not allowed.',
            'detail' => 'This action is unauthorized.'
        ], $results->decodeResponseJson()['error']);
    }

    public function test_delete_unauthorized_user()
    {
        $randomId = rand();
        $results = $this->call('DELETE', '/address/' . $randomId);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized. Please login",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_user_delete_his_address()
    {
        $userId = $this->createAndLogInNewUser();
        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, $userId);

        $results = $this->call('DELETE', '/address/' . $address['id']);

        $this->assertEquals(204, $results->getStatusCode());

    }

    public function test_delete_address_with_orders()
    {
        $userId = $this->createAndLogInNewUser();
        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, $userId);
        $order = [
            'uuid' => $this->faker->uuid,
            'due' => $this->faker->numberBetween(0,1000),
            'tax' => rand(),
            'shipping_costs' => rand(),
            'paid' => rand(),
            'user_id' => $address['user_id'],
            'customer_id' => $address['customer_id'],
            'brand' => ConfigService::$brand,
            'shipping_address_id' => $address['id'],
            'billing_address_id' => null,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableOrder)->insertGetId($order);

        $results = $this->call('DELETE', '/address/' . $address['id']);


        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "Delete failed, exists orders defined for the selected address.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_create_address_with_invalid_country()
    {
        $country = $this->faker->word;
        $type = $this->faker->randomElement([
            AddressService::SHIPPING_ADDRESS,
            AddressService::BILLING_ADDRESS
        ]);
        $userId = rand();
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $streetLine1 = $this->faker->streetAddress;
        $city = $this->faker->city;
        $zip = $this->faker->postcode;
        $state = $this->faker->word;

        $results = $this->call('PUT', '/address', [
            'type' => $type,
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'street_line_1' => $streetLine1,
            'city' => $city,
            'zip' => $zip,
            'state' => $state,
            'country' => $country
        ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "country",
                "detail" => "The country field it's invalid."
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_update_address_with_invalid_country()
    {
        $country = $this->faker->word;
        $userId = $this->createAndLogInNewUser();
        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, $userId);
        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'country' => $country,
            'user_id' => $userId
        ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "country",
                "detail" => "The country field it's invalid."
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_admin_store_user_address()
    {
        $this->createAndLoginAdminUser();

        $type = $this->faker->randomElement([
            AddressService::SHIPPING_ADDRESS,
            AddressService::BILLING_ADDRESS
        ]);

        $randomUserId = rand();
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $streetLine1 = $this->faker->streetAddress;
        $city = $this->faker->city;
        $zip = $this->faker->postcode;
        $state = $this->faker->word;
        $country = $this->faker->randomElement(array_column(Countries::getCountries(), 'full_name'));

        $results = $this->call('PUT', '/address', [
            'type' => $type,
            'user_id' => $randomUserId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'street_line_1' => $streetLine1,
            'city' => $city,
            'zip' => $zip,
            'state' => $state,
            'country' => $country
        ]);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals([
            'id' => 1,
            'type' => $type,
            'brand' => ConfigService::$brand,
            'user_id' => $randomUserId,
            'customer_id' => null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'street_line_1' => $streetLine1,
            'street_line_2' => null,
            'city' => $city,
            'zip' => $zip,
            'state' => $state,
            'country' => $country,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ], $results->decodeResponseJson()['results']);
    }

    public function test_admin_update_user_address()
    {
        $this->createAndLoginAdminUser();

        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, rand());
        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'user_id' => rand(),
            'street_line_1' => $newStreetLine1
        ]);

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals([
            'id' => $address['id'],
            'type' => $address['type'],
            'brand' => $address['brand'],
            'user_id' => $address['user_id'],
            'customer_id' => $address['customer_id'],
            'first_name' => $address['first_name'],
            'last_name' => $address['last_name'],
            'street_line_1' => $newStreetLine1,
            'street_line_2' => $address['street_line_2'],
            'city' => $address['city'],
            'zip' => $address['zip'],
            'state' => $address['state'],
            'country' => $address['country'],
            'created_on' => $address['created_on'],
            'updated_on' => Carbon::now()->toDateTimeString()
        ], $results->decodeResponseJson()['results']);
    }

    public function test_admin_delete_user_address()
    {
        $this->createAndLoginAdminUser();
        $randomId = rand();

        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, $randomId);

        $results = $this->call('DELETE', '/address/' . $address['id']);

        $this->assertEquals(204, $results->getStatusCode());
    }

    public function test_customer_create_address()
    {
        $customer = $this->customerFactory->store();
        $type = $this->faker->randomElement([
            AddressService::SHIPPING_ADDRESS,
            AddressService::BILLING_ADDRESS
        ]);
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $streetLine1 = $this->faker->streetAddress;
        $city = $this->faker->city;
        $zip = $this->faker->postcode;
        $state = $this->faker->word;
        $country = $this->faker->randomElement(array_column(Countries::getCountries(), 'full_name'));

        $results = $this->call('PUT', '/address', [
            'type' => $type,
            'customer_id' => $customer['id'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'street_line_1' => $streetLine1,
            'city' => $city,
            'zip' => $zip,
            'state' => $state,
            'country' => $country
        ]);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals([
            'id' => 1,
            'type' => $type,
            'brand' => ConfigService::$brand,
            'user_id' => null,
            'customer_id' => $customer['id'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'street_line_1' => $streetLine1,
            'street_line_2' => null,
            'city' => $city,
            'zip' => $zip,
            'state' => $state,
            'country' => $country,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ], $results->decodeResponseJson()['results']);
    }

    public function test_customer_update_his_address()
    {
        $customer = $this->customerFactory->store();
        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, null, $customer['id']);
        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'customer_id' => $customer['id'],
            'street_line_1' => $newStreetLine1
        ]);

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals([
            'id' => $address['id'],
            'type' => $address['type'],
            'brand' => $address['brand'],
            'user_id' => $address['user_id'],
            'customer_id' => $address['customer_id'],
            'first_name' => $address['first_name'],
            'last_name' => $address['last_name'],
            'street_line_1' => $newStreetLine1,
            'street_line_2' => $address['street_line_2'],
            'city' => $address['city'],
            'zip' => $address['zip'],
            'state' => $address['state'],
            'country' => $address['country'],
            'created_on' => $address['created_on'],
            'updated_on' => Carbon::now()->toDateTimeString()
        ], $results->decodeResponseJson()['results']);
    }

    public function test_customer_restriction_on_update_other_address()
    {
        $customer = $this->customerFactory->store();
        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, rand(), null);
        $newStreetLine1 = $this->faker->streetAddress;

        $results = $this->call('PATCH', '/address/' . $address['id'], [
            'customer_id' => $customer['id'],
            'street_line_1' => $newStreetLine1
        ]);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_customer_delete_his_address()
    {
        $customer = $this->customerFactory->store();
        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, null, $customer['id']);
        $results = $this->call('DELETE', '/address/' . $address['id'], [
            'customer_id' => $customer['id']
        ]);

        $this->assertEquals(204, $results->getStatusCode());
    }

    public function test_customer_can_not_delete_others_address()
    {
        $customer = $this->customerFactory->store();
        $address = $this->addressFactory->store(AddressService::SHIPPING_ADDRESS, ConfigService::$brand, rand(), null);
        $results = $this->call('DELETE', '/address/' . $address['id'], [
            'customer_id' => $customer['id']
        ]);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }
    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
