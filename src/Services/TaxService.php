<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Services\TaxService;

class TaxService
{
    const DEFAULT_COUNTRY_RATE = 0.5;
    const DEFAULT_RATE = 1;

    /**
     * Calculate the tax rate based on country and region
     *
     * @param Address $address
     *
     * @return float
     */
    public function getTaxRate(?Address $address): float
    {
        if (
            $address &&
            array_key_exists(strtolower($address->getCountry()), ConfigService::$taxRate)
        ) {
            if (
                array_key_exists(
                    strtolower($address->getState()),
                    ConfigService::$taxRate[strtolower($address->getCountry())]
                )
            ) {
                return ConfigService::$taxRate[strtolower($address->getCountry())][strtolower($address->getState())];
            } else {
                return self::DEFAULT_COUNTRY_RATE;
            }
        } else {
            return self::DEFAULT_RATE; // TODO - ask for details
        }
    }

    /**
     * Calculate total taxes based on billing address and the amount that should be paid.
     *
     * @param float $costs
     *
     * @return float
     */
    public function priceWithVat($costs, ?Address $address): float
    {
        return $costs * $this->getTaxRate($address);
    }
}