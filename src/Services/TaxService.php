<?php

namespace Railroad\Ecommerce\Services;


class TaxService
{
    /** Calculate the tax rate based on country and region
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

    /** Calculate the taxes on product and shipping costs for each cart item, the total due, the total taxes, the shipping costs and return an array with the following structure:
     *      'cartItems' => array
     *      'totalDue' => float
     *      'totalTax' => float
     *      'shippingCosts' => float
     *
     * @param array $cartItems
     * @param string $country
     * @param string $region
     * @param int $shippingCosts
     * @return array
     */
    public function calculateTaxesForCartItems($cartItems, $country, $region, $shippingCosts = 0)
    {
        $taxRate = $this->getTaxRate($country, $region);

        $cartItemsTotalDue = array_sum(array_column($cartItems, 'totalPrice'));

        $productsTaxAmount = round($cartItemsTotalDue * $taxRate, 2);

        $shippingTaxAmount = round((float)$shippingCosts * $taxRate, 2);

        //TODO: should be implemented
        $financeCharge = 0;
        $discount = 0;

        $taxAmount = $productsTaxAmount + $shippingTaxAmount;

        $totalDue = round($cartItemsTotalDue -
            $discount +
            $taxAmount +
            (float)$shippingCosts +
            $financeCharge, 2);
        $cartItemsWeight = array_sum(array_column($cartItems, 'weight'));

        foreach ($cartItems as $key => $item) {
            if ($item['totalPrice'] > 0) {
                $cartItems[$key]['itemTax'] = $item['totalPrice'] / ($totalDue - $taxAmount) * $taxAmount;
            }
            $cartItems[$key]['itemShippingCosts'] = $shippingCosts * ($cartItems[$key]['weight']/$cartItemsWeight);
        }
        $results['cartItems'] = $cartItems;
        $results['totalDue'] = $totalDue;
        $results['totalTax'] = $taxAmount;
        $results['shippingCosts'] = (float)$shippingCosts;

        return $results;
    }
}