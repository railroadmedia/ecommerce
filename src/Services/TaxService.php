<?php

namespace Railroad\Ecommerce\Services;


class TaxService
{
    public function getTaxRate($country, $region)
    {
        if (array_key_exists($country, ConfigService::$taxRate)) {
            if (array_key_exists($region, ConfigService::$taxRate[$country])) {
                return ConfigService::$taxRate[$country][$region];
            } else {
                return 0.05;
            }
        } else {
            return 0;
        }
    }

    public function getCartItemsWithTax($cartItems, $country, $region, $shippingCosts)
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

        foreach ($cartItems as $key => $item) {
            if ($item->totalPrice > 0) {
                $cartItems[$key]->itemTax = $item->totalPrice / ($totalDue - $taxAmount) * $taxAmount;
            }
        }
        $cartItems['totalDue'] = $totalDue;
        $cartItems['totalTax'] = $taxAmount;
        $cartItems['shippingCosts'] = (float)$shippingCosts;

        return $cartItems;
    }
}