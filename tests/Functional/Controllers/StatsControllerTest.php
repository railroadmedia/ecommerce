<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Railroad\Ecommerce\Tests\EcommerceTestCase;

class StatsControllerTest extends EcommerceTestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testStatsProduct()
    {
        $results = $this->call(
            'GET',
            '/stats/products/',
            [
                'brand' => 'recordeo',
                'start-date' => '2018-06-20 00:00:00',
                'end-date' => '2018-06-20 23:19:08',
            ]
        );
        //dd($results->decodeResponseJson('data'));
        $this->assertTrue(true);
    }
}
