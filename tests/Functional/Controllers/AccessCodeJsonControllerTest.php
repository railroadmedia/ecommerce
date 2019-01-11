<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class AccessCodeJsonControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    // public function test_get_all_access_codes_when_empty()
    // {
    //     $this->permissionServiceMock->method('can')->willReturn(true);

    //     $expectedResults = [
    //         'data' => [],
    //         'meta' => [
    //             'totalResults' => 0,
    //             'page' => 1,
    //             'limit' => 10
    //         ]
    //     ];

    //     $results = $this->call('GET', '/access-codes');

    //     $this->assertEquals(200, $results->status());

    //     $results->assertJson($expectedResults);
    // }

    // public function test_admin_get_all_access_codes()
    // {
    //     $this->permissionServiceMock->method('can')->willReturn(true);

    //     $page = 1;
    //     $limit = 30;
    //     $sort = 'id';
    //     $nrAccessCodes = 10;
    //     $accessCodes = [];

    //     for($i = 0; $i < $nrAccessCodes; $i++) {
    //         $accessCode = $this->accessCodeRepository->create($this->faker->accessCode());
    //         $accessCodes[] = iterator_to_array($accessCode) + ['claimer' => null];
    //     }

    //     $results = $this->call('GET', '/access-codes', [
    //         'page' => $page,
    //         'limit' => $limit,
    //         'order_by_column' => $sort,
    //         'order_by_direction' => 'asc'
    //     ]);

    //     $this->assertEquals(200, $results->status());

    //     $this->assertEquals($accessCodes, $results->decodeResponseJson('data'));
    // }

    // public function test_admin_search_access_codes()
    // {
    //     $this->permissionServiceMock->method('can')->willReturn(true);

    //     $nrAccessCodes = 10;
    //     $accessCodes = [];

    //     for($i = 0; $i < $nrAccessCodes; $i++) {
    //         $accessCode = $this->accessCodeRepository->create($this->faker->accessCode());
    //         $accessCodes[] = iterator_to_array($accessCode);
    //     }

    //     $selectedAccessCodeIndex = $this->faker->numberBetween(0, $nrAccessCodes - 1);
    //     $selectedAccessCode = $accessCodes[$selectedAccessCodeIndex] + ['claimer' => null];
    //     $selectedCodeLength = strlen($selectedAccessCode['code']);
    //     $codeFragment = substr(
    //         $selectedAccessCode['code'],
    //         $this->faker->numberBetween(0, intdiv($selectedCodeLength, 2)),
    //         $this->faker->numberBetween(3, intdiv($selectedCodeLength, 2))
    //     );

    //     $response = $this->call('GET', '/access-codes/search', [
    //         'term' => $codeFragment
    //     ]);

    //     $this->assertContains($selectedAccessCode, $response->decodeResponseJson()['data']);
    // }

    public function test_claim_validation()
    {
        $response = $this->call('POST', '/access-codes/claim', []);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals([
            [
                'source' => 'access_code',
                'detail' => 'The access code field is required.',
            ],
            [
                'source' => 'claim_for_user_email',
                'detail' => 'The claim for user email field is required.',
            ]
        ], $response->decodeResponseJson('meta')['errors']);
    }

    public function test_claim_for_user_email_not_found()
    {
        $userId  = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $email = $this->faker->email;

        $response = $this->call('POST', '/access-codes/claim', [
            'access_code' => $accessCode['code'],
            'claim_for_user_email' => $email
        ]);

        //assert the response status code
        $this->assertEquals(404, $response->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals(
            [
                'title' => 'Not found.',
                'detail' => 'Claim failed, user not found with email: ' . $email,
            ],
            $response->decodeResponseJson('meta')['errors']
        );
    }

    public function test_claim()
    {
        $adminId  = $this->createAndLogInNewUser();

        $user = $this->fakeUser();

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $response = $this->call('POST', '/access-codes/claim', [
            'access_code' => $accessCode['code'],
            'claim_for_user_email' => $user['email']
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $user['id'],
                'product_id' => $product['id'],
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString()
            ]
        );

        // assert access code was set as claimed
        $this->assertDatabaseHas(
            ConfigService::$tableAccessCode,
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_release()
    {
        $userId  = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 1,
            'claimed_on' => Carbon::now()->toDateTimeString()
        ]);

        $response = $this->call('POST', '/access-codes/release', [
            'access_code_id' => $accessCode['id']
        ]);

        //assert the response status code
        $this->assertEquals(200, $response->getStatusCode());

        // assert access code was set as claimed
        $this->assertDatabaseHas(
            ConfigService::$tableAccessCode,
            [
                'id' => $accessCode['id'],
                'is_claimed' => false,
                'claimer_id' => null,
                'claimed_on' => null
            ]
        );
    }

    public function test_release_validation()
    {
        $response = $this->call('POST', '/access-codes/release', []);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals([
            [
                'source' => 'access_code_id',
                'detail' => 'The access code id field is required.',
            ]
        ], $response->decodeResponseJson('meta')['errors']);
    }

    public function test_release_validation_unclaimed()
    {
        $userId  = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $response = $this->call('POST', '/access-codes/release', [
            'access_code_id' => $accessCode['id']
        ]);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals([
            [
                'source' => 'access_code_id',
                'detail' => 'The selected access code id is invalid.',
            ]
        ], $response->decodeResponseJson('meta')['errors']);
    }
}
