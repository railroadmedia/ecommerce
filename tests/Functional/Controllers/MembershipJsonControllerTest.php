<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class MembershipJsonControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_pull_stats()
    {
        $smallDate = Carbon::now()->subDays(15);
        $bigDate = Carbon::now()->subDays(10);

        $expected = ['data' => []];

        // should not be returned
        $this->fakeMembershipStats(['stats_date' => Carbon::now()->subDays(16)->toDateString()]);

        $statsOne = $this->fakeMembershipStats(['stats_date' => Carbon::now()->subDays(15)->toDateString()]);
        $expected['data'][] = [
            'id' => $statsOne['id'],
            'type' => 'membershipStats',
            'attributes' => array_diff_key(
                $statsOne,
                ['id' => true]
            )
        ];

        $statsTwo = $this->fakeMembershipStats(['stats_date' => Carbon::now()->subDays(12)->toDateString()]);
        $expected['data'][] = [
            'id' => $statsTwo['id'],
            'type' => 'membershipStats',
            'attributes' => array_diff_key(
                $statsTwo,
                ['id' => true]
            )
        ];

        $statsThree = $this->fakeMembershipStats(['stats_date' => Carbon::now()->subDays(10)->toDateString()]);
        $expected['data'][] = [
            'id' => $statsThree['id'],
            'type' => 'membershipStats',
            'attributes' => array_diff_key(
                $statsThree,
                ['id' => true]
            )
        ];

        // should not be returned
        $this->fakeMembershipStats(['stats_date' => Carbon::now()->subDays(9)->toDateString()]);

        $response = $this->call(
            'GET',
            '/membership-stats',
            [
                'small_date_time' => $smallDate->toDateTimeString(),
                'big_date_time' => $bigDate->toDateTimeString(),
            ]
        );

        $this->assertEquals(
            $expected,
            $response->decodeResponseJson()
        );
    }
}
