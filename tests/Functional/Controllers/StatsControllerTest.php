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
                'brand' => 'drumeo',
                'start-date' => '2018-07-05 00:00:00',
                'end-date' => '2018-07-05 23:59:59',
            ]
        );
       // dd($results->decodeResponseJson('data'));
        $this->assertTrue(true);
    }
}
