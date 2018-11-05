<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class AccessCodeJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var AccessCodeRepository
     */
    protected $accessCodeRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->accessCodeRepository = $this->app->make(AccessCodeRepository::class);
    }

    public function test_get_all_access_codes_when_empty()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $expectedResults = [
            'data' => [],
            'meta' => [
                'totalResults' => 0,
                'page' => 1,
                'limit' => 10
            ]
        ];

        $results = $this->call('GET', '/access-codes');

        $this->assertEquals(200, $results->status());

        $results->assertJson($expectedResults);
    }

    public function test_admin_get_all_access_codes()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $page = 1;
        $limit = 30;
        $sort = 'id';
        $nrAccessCodes = 10;
        $accessCodes = [];

        for($i = 0; $i < $nrAccessCodes; $i++) {
            $accessCode = $this->accessCodeRepository->create($this->faker->accessCode());
            $accessCodes[] = iterator_to_array($accessCode);
        }

        $results = $this->call('GET', '/access-codes', [
            'page' => $page,
            'limit' => $limit,
            'order_by_column' => $sort,
            'order_by_direction' => 'asc'
        ]);

        $this->assertEquals(200, $results->status());

        $this->assertEquals($accessCodes, $results->decodeResponseJson('data'));
    }

    public function test_admin_search_access_codes()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $nrAccessCodes = 10;
        $accessCodes = [];

        for($i = 0; $i < $nrAccessCodes; $i++) {
            $accessCode = $this->accessCodeRepository->create($this->faker->accessCode());
            $accessCodes[] = iterator_to_array($accessCode);
        }

        $selectedAccessCodeIndex = $this->faker->numberBetween(0, $nrAccessCodes - 1);
        $selectedAccessCode = $accessCodes[$selectedAccessCodeIndex];
        $selectedCodeLength = strlen($selectedAccessCode['code']);
        $codeFragment = substr(
            $selectedAccessCode['code'],
            $this->faker->numberBetween(0, intdiv($selectedCodeLength, 2)),
            $this->faker->numberBetween(3, intdiv($selectedCodeLength, 2))
        );

        $response = $this->call('GET', '/access-codes/search', [
            'term' => $codeFragment
        ]);

        $this->assertContains($selectedAccessCode, $response->decodeResponseJson()['data']);
    }
}
