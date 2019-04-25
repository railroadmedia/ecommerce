<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Services\ConfigService;

class TaxServiceTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_get_tax_rate_set()
    {
        $srv = $this->app->make(TaxService::class);

        $country = 'canada';
        $state = $this->faker->randomElement(array_keys(ConfigService::$taxRate[$country]));

        $address = new Address();

        $address
            ->setCountry($country)
            ->setState($state);

        $this->assertEquals(ConfigService::$taxRate[$country][$state], $srv->getTaxRate($address));
    }

    public function test_get_tax_rate_unset_state()
    {
        $srv = $this->app->make(TaxService::class);

        $country = 'canada';
        $state = '';

        $address = new Address();

        $address
            ->setCountry($country)
            ->setState($state);

        $this->assertEquals(
            ConfigService::$taxRate[$country][TaxService::DEFAULT_STATE_KEY],
            $srv->getTaxRate($address)
        );
    }

    public function test_get_tax_rate_unset_country()
    {
        $srv = $this->app->make(TaxService::class);

        $country = '';
        $state = '';

        $address = new Address();

        $address
            ->setCountry($country)
            ->setState($state);

        $this->assertEquals(TaxService::DEFAULT_RATE, $srv->getTaxRate($address));
    }

    public function test_get_tax_total()
    {
        $srv = $this->app->make(TaxService::class);

        $price = 100;
        $country = 'canada';
        $state = $this->faker->randomElement(array_keys(ConfigService::$taxRate[$country]));

        $address = new Address();

        $address
            ->setCountry($country)
            ->setState($state);

        $vat = ConfigService::$taxRate[$country][$state] * $price;

        $this->assertEquals($vat, $srv->vat($price, $address));
    }
}
