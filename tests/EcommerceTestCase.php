<?php

namespace Railroad\Ecommerce\Tests;

use Carbon\Carbon;
use Orchestra\Testbench\TestCase as BaseTestCase;


class EcommerceTestCase extends BaseTestCase
{

    protected function setUp()
    {
        parent::setUp();

        $this->artisan('migrate:fresh', []);
        $this->artisan('cache:clear', []);

        Carbon::setTestNow(Carbon::now());
    }

    protected function tearDown()
    {
        parent::tearDown();
    }



}