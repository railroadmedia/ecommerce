<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Exception;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class TaxServiceTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_get_total_tax_due()
    {
        $srv = $this->app->make(TaxService::class);

        $price = 100;
        $shipping = 10;
        $country = 'canada';
        $region = 'british columbia';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $this->assertEquals(12.5, $srv->getTaxesDueTotal($price, $shipping, $address));
    }

    public function test_get_product_tax_rate()
    {
        $srv = $this->app->make(TaxService::class);

        $country = 'canada';
        $region = $this->faker->randomElement(array_keys(config('ecommerce.product_tax_rate.canada')));

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $expectedTaxRateProduct = config('ecommerce.product_tax_rate')[$country][$region];

        if (isset(config('ecommerce.qst_tax_rate')[$country][$region])) {
            $expectedTaxRateProduct += config('ecommerce.qst_tax_rate')[$country][$region];
        }

        $this->assertEquals(
            $expectedTaxRateProduct,
            $srv->getProductTaxRate($address)
        );
    }

    public function test_get_product_tax_rate_unset_region()
    {
        $srv = $this->app->make(TaxService::class);

        $country = 'canada';
        $region = '';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $rate = $srv->getProductTaxRate($address);

        $this->assertEquals(0, $rate);
    }

    public function test_get_product_tax_rate_unset_country()
    {
        $srv = $this->app->make(TaxService::class);

        $country = '';
        $region = '';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $rate = $srv->getProductTaxRate($address);

        $this->assertEquals(0, $rate);
    }

    public function test_get_shipping_tax_rate()
    {
        $srv = $this->app->make(TaxService::class);

        $country = 'canada';
        $region = $this->faker->randomElement(array_keys(config('ecommerce.product_tax_rate.canada')));

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $expectedTaxRateShipping = config('ecommerce.shipping_tax_rate')[$country][$region];

        if (isset(config('ecommerce.qst_tax_rate')[$country][$region])) {
            $expectedTaxRateShipping += config('ecommerce.qst_tax_rate')[$country][$region];
        }

        $this->assertEquals(
            $expectedTaxRateShipping,
            $srv->getShippingTaxRate($address)
        );
    }

    public function test_get_gst_tax_rate()
    {
        $srv = $this->app->make(TaxService::class);

        $country = 'canada';
        $region = $this->faker->randomElement(array_keys(config('ecommerce.product_tax_rate.canada')));

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $this->assertEquals(
            config('ecommerce.gst_hst_tax_rate_display_only')[$country][$region],
            $srv->getGSTTaxRate($address)
        );
    }

    public function test_get_tax_due_for_product()
    {
        $srv = $this->app->make(TaxService::class);

        $price = 100;
        $country = 'canada';
        $region = 'british columbia';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $this->assertEquals(12, $srv->getTaxesDueForProductCost($price, $address));
    }

    public function test_get_tax_due_for_shipping_bc()
    {
        $srv = $this->app->make(TaxService::class);

        $price = 100;
        $country = 'canada';
        $region = 'british columbia';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $this->assertEquals(5, $srv->getTaxesDueForShippingCost($price, $address));
    }

    public function test_get_tax_due_for_gst_bc()
    {
        $srv = $this->app->make(TaxService::class);

        $price = 100;
        $country = 'canada';
        $region = 'british columbia';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $this->assertEquals(5, $srv->getTaxesDueForShippingCost($price, $address));
    }
}
