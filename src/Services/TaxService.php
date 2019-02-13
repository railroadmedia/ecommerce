<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Services\TaxService;

class TaxService
{
    const DEFAULT_STATE_KEY = 'default';
    const DEFAULT_RATE = 0;

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
            } else if (
                array_key_exists(
                    strtolower(self::DEFAULT_STATE_KEY),
                    ConfigService::$taxRate[strtolower($address->getCountry())]
                )
            ) {
                return ConfigService::$taxRate[strtolower($address->getCountry())][self::DEFAULT_STATE_KEY];
            } else {
                return self::DEFAULT_RATE;
            }
        } else {
            return self::DEFAULT_RATE;
        }
    }

    /**
     * Calculate total taxes based on billing address and the amount that should be paid.
     *
     * @param float $costs
     *
     * @return float
     */
    public function vat($costs, ?Address $address): float
    {
        return $costs * $this->getTaxRate($address);
    }
}