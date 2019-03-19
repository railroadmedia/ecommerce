<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Tests\Fixtures\UserProvider;

class AccessCodeJsonControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_admin_get_all_access_codes()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $page = 1;
        $limit = 3;
        $sort = 'id';
        $nrAccessCodes = 5;
        $accessCodes = [];

        for($i = 0; $i < $nrAccessCodes; $i++) {

            $productIds = [];

            $product = $this->fakeProduct([
                'type' => ConfigService::$typeSubscription,
                'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                'subscription_interval_count' => 1,
            ]);

            $productIds[] = $product['id'];

            $product = $this->fakeProduct([
                'type' => ConfigService::$typeSubscription,
                'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                'subscription_interval_count' => 1,
            ]);

            $productIds[] = $product['id'];

            $accessCode = $this->fakeAccessCode([
                'is_claimed' => true,
                'claimed_on' => Carbon::now()->toDateTimeString(),
                'product_ids' => $productIds,
                'claimer_id' => $userId
            ]);

            $accessCodes[] = $accessCode;
        }

        $results = $this->call(
            'GET',
            '/access-codes',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => $sort,
                'order_by_direction' => 'asc'
            ]
        );

        $this->assertEquals(200, $results->status());

        $results = $results->decodeResponseJson();

        $aIndex = 0;

        foreach ($results['data'] as $accessCodeResponse) {

            // assert each response access code is in the faked/seeded list
            $this->assertArraySubset(
                [$aIndex => [
                    'id' => $accessCodeResponse['id'],
                    'code' => $accessCodeResponse['attributes']['code'],
                    'brand' => $accessCodeResponse['attributes']['brand'],
                    'product_ids' => serialize($accessCodeResponse['attributes']['product_ids']),
                    'is_claimed' => $accessCodeResponse['attributes']['is_claimed'],
                    'claimed_on' => $accessCodeResponse['attributes']['claimed_on'],
                ]],
                $accessCodes
            );

            // assert each response access code has the claimer relation data set
            $this->assertEquals(
                [
                    'data' => [
                        'type' => 'user',
                        'id' => $userId
                    ]
                ],
                $accessCodeResponse['relationships']['claimer']
            );

            $pIndex = 0;

            foreach ($accessCodeResponse['attributes']['product_ids'] as $productId) {

                // assert each response access code has the product relation data set
                $this->assertArraySubset(
                    [
                        $pIndex => [
                            'type' => 'product',
                            'id' => $productId,
                        ]
                    ],
                    $accessCodeResponse['relationships']['product']['data']
                );

                $pIndex++;
            }

            $aIndex++;
        }

        // assert results count
        $this->assertEquals(count($results['data']), $limit);
    }

    public function test_admin_search_access_codes()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $nrAccessCodes = 10;
        $accessCodes = [];

        for($i = 0; $i < $nrAccessCodes; $i++) {
            $product = $this->fakeProduct([
                'type' => ConfigService::$typeSubscription,
                'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                'subscription_interval_count' => 1,
            ]);

            $accessCode = $this->fakeAccessCode([
                'is_claimed' => true,
                'claimed_on' => Carbon::now()->toDateTimeString(),
                'product_ids' => [$product['id']],
                'claimer_id' => $userId
            ]);

            $accessCodes[] = $accessCode;
        }

        $selectedAccessCodeIndex = $this->faker->numberBetween(0, $nrAccessCodes - 1);
        $selectedAccessCode = $accessCodes[$selectedAccessCodeIndex];
        $selectedAccessCodeProduct = unserialize($selectedAccessCode['product_ids']);
        $selectedAccessCodeProduct = reset($selectedAccessCodeProduct);
        $selectedCodeLength = strlen($selectedAccessCode['code']);
        $codeFragment = substr(
            $selectedAccessCode['code'],
            $this->faker->numberBetween(0, intdiv($selectedCodeLength, 2)),
            $this->faker->numberBetween(3, intdiv($selectedCodeLength, 2))
        );

        $response = $this->call('GET', '/access-codes/search', [
            'term' => $codeFragment
        ]);

        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'id' => $selectedAccessCode['id'],
                        'type' => 'accessCode',
                        'attributes' => array_diff_key(
                            $selectedAccessCode,
                            [
                                'id' => true,
                                'claimer_id' => true,
                                'product_ids' => true
                            ]
                        ),
                        'relationships' => [
                            'claimer' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId
                                ]
                            ],
                            'product' => [
                                'data' => [
                                    [
                                        'type' => 'product',
                                        'id' => $selectedAccessCodeProduct
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_claim_validation()
    {
        $response = $this->call('POST', '/access-codes/claim', []);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals([
            [
                'title' => 'Validation failed.',
                'source' => 'access_code',
                'detail' => 'The access code field is required.',
            ],
            [
                'title' => 'Validation failed.',
                'source' => 'claim_for_user_id',
                'detail' => 'The claim for user id field is required.',
            ]
        ], $response->decodeResponseJson('errors'));
    }

    public function test_claim_for_user_id_not_found()
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

        $userId = rand();

        // mock UserProvider
        $userProviderMock = $this->getMockBuilder(UserProvider::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->app->extend(
            UserProviderInterface::class,
            function ($service) use ($userProviderMock) {

                return $userProviderMock;
            }
        );

        $userProviderMock
            ->method('getUserById')
            ->willReturn(null);

        $response = $this->call('POST', '/access-codes/claim', [
            'access_code' => $accessCode['code'],
            'claim_for_user_id' => $userId
        ]);

        //assert the response status code
        $this->assertEquals(404, $response->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals(
            [
                'title' => 'Not found.',
                'detail' => 'Claim failed, user not found with id: ' . $userId,
            ],
            $response->decodeResponseJson('errors')
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
            'claim_for_user_id' => $user['id']
        ]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'id' => $accessCode['id'],
                    'type' => 'accessCode',
                    'attributes' => array_merge(
                        array_diff_key(
                            $accessCode,
                            [
                                'id' => true,
                                'claimer_id' => true,
                                'product_ids' => true
                            ]
                        ),
                        [
                            'product_ids' => [$product['id']],
                            'is_claimed' => true,
                            'claimed_on' => Carbon::now()->toDateTimeString()
                        ]
                    ),
                    'relationships' => [
                        'claimer' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $user['id']
                            ]
                        ]
                    ]
                ],
            ],
            $response->decodeResponseJson()
        );

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

        $this->assertArraySubset(
            [
                'data' => [
                    'id' => $accessCode['id'],
                    'type' => 'accessCode',
                    'attributes' => array_merge(
                        array_diff_key(
                            $accessCode,
                            [
                                'id' => true,
                                'product_ids' => true,
                                'claimer_id' => null
                            ]
                        ),
                        [
                            'product_ids' => [$product['id']],
                            'is_claimed' => false,
                            'claimed_on' => null
                        ]
                    )
                ],
            ],
            $response->decodeResponseJson()
        );

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
                'title' => 'Validation failed.',
                'source' => 'access_code_id',
                'detail' => 'The access code id field is required.',
            ]
        ], $response->decodeResponseJson('errors'));
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
                'title' => 'Validation failed.',
                'source' => 'access_code_id',
                'detail' => 'The selected access code id is invalid.',
            ]
        ], $response->decodeResponseJson('errors'));
    }
}
