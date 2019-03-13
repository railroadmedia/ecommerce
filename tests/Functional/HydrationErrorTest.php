<?php

namespace Railroad\Ecommerce\Tests\Functional;

use ErrorException;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;

class HydrationErrorTest extends EcommerceTestCase
{
    public function test_no_association_hydration_persist()
    {
        $hydrator = app()->make(JsonApiHydrator::class);
        $entityManager = app()->make(EcommerceEntityManager::class);

        // create flat db-style structure
        $addressData = [
            'type' => ConfigService::$shippingAddressType,
            'brand' => $this->faker->word,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName
        ];

        // crate json api structure
        $jsonApiAddressData = [
            'attributes' => $addressData
        ];

        $address = new Address();

        // hydrate new address object
        $hydrator->hydrate($address, $jsonApiAddressData);

        // persist hydrated address object
        $entityManager->persist($address);
        $entityManager->flush();

        // decorate flat db-style structure
        $addressData['id'] = $address->getId();
        $addressData['customer_id'] = null;
        $addressData['user_id'] = null;

        // assert db persist results
        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            $addressData
        );
    }

    public function test_association_persist()
    {
        $hydrator = app()->make(JsonApiHydrator::class);
        $entityManager = app()->make(EcommerceEntityManager::class);

        // create a customer entity and persist
        $customerData = [
            'brand' => $this->faker->word,
            'email' => $this->faker->email
        ];

        $customer = new Customer();

        $customer
            ->setBrand($customerData['brand'])
            ->setEmail($customerData['email']);

        $entityManager->persist($customer);
        $entityManager->flush();

        $customerData['id'] = $customer->getId();

        // create an address entity and associate the customer object
        $addressData = [
            'type' => ConfigService::$shippingAddressType,
            'brand' => $this->faker->word,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName
        ];

        $address = new Address();

        $address
            ->setType($addressData['type'])
            ->setBrand($addressData['brand'])
            ->setFirstName($addressData['first_name'])
            ->setLastName($addressData['last_name'])
            ->setCustomer($customer);

        $entityManager->persist($address);
        $entityManager->flush();

        $addressData['id'] = $address->getId();
        $addressData['customer_id'] = $customer->getId();

        // assert db persist results
        $this->assertDatabaseHas(
            ConfigService::$tableCustomer,
            $customerData
        );

        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            $addressData
        );
    }

    public function test_association_hydration_persist()
    {
        $hydrator = app()->make(JsonApiHydrator::class);
        $entityManager = app()->make(EcommerceEntityManager::class);

        // create a customer entity and persist
        $customerData = [
            'brand' => $this->faker->word,
            'email' => $this->faker->email
        ];

        $customer = new Customer();

        $customer
            ->setBrand($customerData['brand'])
            ->setEmail($customerData['email']);

        $entityManager->persist($customer);
        $entityManager->flush();

        $customerData['id'] = $customer->getId();

        // assert db persist results
        $this->assertDatabaseHas(
            ConfigService::$tableCustomer,
            $customerData
        );

        // clear the entity manager customer data
        $this->entityManager->detach($customer);
        unset($customer);

        // create flat address data
        $addressData = [
            'type' => ConfigService::$shippingAddressType,
            'brand' => $this->faker->word,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName
        ];

        // crate json api address data with association
        $jsonApiAddressData = [
            'attributes' => $addressData,
            'relationships' => [
                'customer' => [
                    'data' => [
                        'type' => 'customer',
                        'id' => $customerData['id']
                    ],
                ],
            ],
        ];

        $address = new Address();

        // hydrate new address object
        $hydrator->hydrate($address, $jsonApiAddressData);

        // persist hydrated address object
        $entityManager->persist($address);

        /*
        flush will throw exception because
        JsonApiHydrator makes use of doctrine package entity manager (EntityManagerInterface container binding)
        the hydrated associated customer is not managed by EcommerceEntityManager
        */
        $this->expectException(ErrorException::class);

        $entityManager->flush();
    }
}