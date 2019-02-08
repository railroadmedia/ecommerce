<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Entities\Address;
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
            ->setType($this->faker->word)
            ->setBrand($this->faker->word)
            ->setCreatedAt(Carbon::now())
            ->setCountry($country)
            ->setState($state);

        // TODO update after finishing tax service
        $this->assertEquals(ConfigService::$taxRate[$country][$state], $srv->getTaxRate($address));
    }

    public function test_get_tax_rate_unset_state()
    {
        $srv = $this->app->make(TaxService::class);

        $country = 'canada';
        $state = '';

        $address = new Address();

        $address
            ->setType($this->faker->word)
            ->setBrand($this->faker->word)
            ->setCreatedAt(Carbon::now())
            ->setCountry($country)
            ->setState($state);

        // TODO update after finishing tax service
        $this->assertEquals(TaxService::DEFAULT_COUNTRY_RATE, $srv->getTaxRate($address));
    }

    public function test_get_tax_rate_unset_country()
    {
        $srv = $this->app->make(TaxService::class);

        $country = '';
        $state = '';

        $address = new Address();

        $address
            ->setType($this->faker->word)
            ->setBrand($this->faker->word)
            ->setCreatedAt(Carbon::now())
            ->setCountry($country)
            ->setState($state);

        // TODO update after finishing tax service
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
            ->setType($this->faker->word)
            ->setBrand($this->faker->word)
            ->setCreatedAt(Carbon::now())
            ->setCountry($country)
            ->setState($state);

        $priceWithVat = ConfigService::$taxRate[$country][$state] * $price;

        // TODO update after finishing tax service
        $this->assertEquals($priceWithVat, $srv->priceWithVat($price, $address));
    }
}
