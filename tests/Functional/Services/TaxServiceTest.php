<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

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

    public function test_get_total_tax_due_payment_gateway_not_blacklisted()
    {
        config()->set('ecommerce.brand', 'drumeo');

        $srv = $this->app->make(TaxService::class);

        $price = 100; // 14.975
        $shipping = 10; // 0.50
        $country = 'canada';
        $region = 'quebec';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $this->assertEquals(15.48, $srv->getTaxesDueTotal($price, $shipping, $address));
    }

    public function test_get_total_tax_due_payment_gateway_is_blacklisted_by_config()
    {
        config()->set('ecommerce.brand', 'pianote');

        $srv = $this->app->make(TaxService::class);

        $price = 100; // 5
        $shipping = 10; // 0.50
        $country = 'canada';
        $region = 'quebec';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $this->assertEquals(5.50, $srv->getTaxesDueTotal($price, $shipping, $address));
    }

    public function test_get_total_tax_due_payment_gateway_is_blacklisted_by_request()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);
        request()->merge(['brand' => 'pianote']);

        $srv = $this->app->make(TaxService::class);

        $price = 100; // 5
        $shipping = 10; // 0.50
        $country = 'canada';
        $region = 'quebec';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $this->assertEquals(5.50, $srv->getTaxesDueTotal($price, $shipping, $address));
    }

    public function test_get_product_tax_rate()
    {
        $srv = $this->app->make(TaxService::class);

        foreach (config('ecommerce.tax_rates_and_options') as $countryName => $countryOptions) {
            foreach ($countryOptions as $regionName => $regionOptions) {

                $expectedRate = 0;

                foreach ($regionOptions as $regionOption) {
                    $expectedRate += $regionOption['rate'];
                }

                $address = new Address();

                $address->setCountry($countryName);
                $address->setRegion($regionName);

                $this->assertEquals(
                    $expectedRate,
                    $srv->getProductTaxRate($address)
                );

// for debugging
//                echo $countryName .
//                    ' - ' .
//                    $regionName .
//                    ': ' .
//                    $expectedRate .
//                    '|' .
//                    $srv->getProductTaxRate($address) .
//                    "\n";
            }
        }
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

    public function test_get_product_tax_rate_non_taxed_country()
    {
        $srv = $this->app->make(TaxService::class);

        $country = 'united states';
        $region = 'ohio';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $rate = $srv->getProductTaxRate($address);

        $this->assertEquals(0, $rate);
    }

    public function test_taxes_no_config()
    {
        config()->set('ecommerce.tax_rates_and_options', null);

        $srv = $this->app->make(TaxService::class);

        $country = 'united states';
        $region = 'ohio';

        $address = new Address();

        $address->setCountry($country);
        $address->setRegion($region);

        $rate = $srv->getProductTaxRate($address);

        $this->assertEquals(0, $rate);
    }

    public function test_get_shipping_tax_rate()
    {
        $srv = $this->app->make(TaxService::class);

        foreach (config('ecommerce.tax_rates_and_options') as $countryName => $countryOptions) {
            foreach ($countryOptions as $regionName => $regionOptions) {

                $expectedRate = 0;

                foreach ($regionOptions as $regionOption) {
                    if (isset($regionOption['applies_to_shipping_costs']) &&
                        $regionOption['applies_to_shipping_costs'] == true) {

                        $expectedRate += $regionOption['rate'];
                    }
                }

                $address = new Address();

                $address->setCountry($countryName);
                $address->setRegion($regionName);

                $this->assertEquals(
                    $expectedRate,
                    $srv->getShippingTaxRate($address)
                );

// for debugging
//                echo $countryName .
//                    ' - ' .
//                    $regionName .
//                    ': ' .
//                    $expectedRate .
//                    '|' .
//                    $srv->getShippingTaxRate($address) .
//                    "\n";
            }
        }
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

    public function test_get_tax_amounts_per_type()
    {
        $srv = $this->app->make(TaxService::class);

        $price = 100;
        $shipping = 10;

        foreach (config('ecommerce.tax_rates_and_options') as $countryName => $countryOptions) {
            foreach ($countryOptions as $regionName => $regionOptions) {

                $expectedTypeRates = [];

                foreach ($regionOptions as $regionOption) {
                    $expectedTypeRates[$regionOption['type']] = 0;

                    if ($shipping > 0 && isset($regionOption['applies_to_shipping_costs']) &&
                        $regionOption['applies_to_shipping_costs'] == true) {

                        $expectedTypeRates[$regionOption['type']] += ($regionOption['rate'] * $shipping);
                    }

                    $expectedTypeRates[$regionOption['type']] += ($regionOption['rate'] * $price);

                    $expectedTypeRates[$regionOption['type']] = round($expectedTypeRates[$regionOption['type']], 2);
                }

                $address = new Address();

                $address->setCountry($countryName);
                $address->setRegion($regionName);

                $this->assertEquals(
                    $expectedTypeRates,
                    $srv->getTaxesDuePerType($price, $shipping, $address)
                );

                // for debugging
//                var_dump($srv->getTaxesDuePerType($price, $shipping, $address));
            }
        }
    }
}
