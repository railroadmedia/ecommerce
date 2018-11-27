<?php

namespace Railroad\Ecommerce\Services;

class TaxService
{
    /** Calculate the tax rate based on country and region
     *
     * @param string $country
     * @param string $region
     * @return float|int
     */
    public function getTaxRate($country, $region)
    {
        if (array_key_exists(strtolower($country), ConfigService::$taxRate)) {
            if (array_key_exists(strtolower($region), ConfigService::$taxRate[strtolower($country)])) {
                return ConfigService::$taxRate[strtolower($country)][strtolower($region)];
            } else {
                return 0.05;
            }
        } else {
            return 0;
        }
    }


    /** Calculate total taxes based on billing address and the amount that should be paid.
     *
     * @param integer $costs
     * @return float|int
     */
    public function getTaxTotal($costs, $country, $region)
    {
        return $costs * $this->getTaxRate($country, $region);
    }


}