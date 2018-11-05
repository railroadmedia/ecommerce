<?php

namespace Railroad\Ecommerce\Entities;

use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Resora\Entities\Entity;

class CartItem extends Entity
{
    /**
     * @var Product
     */
    public $product;

    /**
     * @var integer
     */
    public $quantity;

    /**
     * CartItem constructor.
     *
     * @param Product $product
     * @param int $quantity
     */
    public function __construct(Product $product, $quantity)
    {
        $this->product = $product;
        $this->quantity = $quantity;
    }

    /**
     * @param array $applicableDiscounts
     * @return float
     */
    public function getPriceAfterDiscounts(array $applicableDiscounts = [])
    {
        return $this->getPriceBeforeDiscounts() - $this->getAmountDiscountedByProductDiscounts($applicableDiscounts);
    }

    /**
     * @param array $applicableDiscounts
     * @return float|int
     */
    public function getAmountDiscountedByProductDiscounts(array $applicableDiscounts = [])
    {
        $amountDiscounted = 0;

        foreach ($applicableDiscounts as $discount) {

            if ($this->product['id'] == $discount['product_id']) {
                if ($discount['type'] == DiscountService::PRODUCT_AMOUNT_OFF_TYPE ||
                    $discount['type'] == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {

                    $amountDiscounted += $discount['amount'];

                } elseif ($discount['type'] == DiscountService::PRODUCT_PERCENT_OFF_TYPE) {

                    $amountDiscounted += round($this->getPriceBeforeDiscounts() * $discount['amount'] / 100, 2);
                } elseif ($discount['type'] == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {

                    return $this->getPriceBeforeDiscounts();
                }
            }
        }

        return $amountDiscounted;
    }

    /**
     * @return float
     */
    public function getPriceBeforeDiscounts()
    {
        return $this->quantity * $this->product['price'];
    }

    /**
     * @param Cart $cart
     * @return float
     */
    public function calculateCutOfOrderTotal(Cart $cart)
    {
        $discountRatio = $cart->getDiscountRatio();

        return round($this->getPriceAfterDiscounts($cart->getApplicableDiscounts()) * $discountRatio, 2);
    }

    /**
     * @param Cart $cart
     * @return float
     */
    public function calculateTotalDiscounted(Cart $cart)
    {
        $discountRatio = $cart->getDiscountRatio();

        return round($this->getAmountDiscountedByProductDiscounts() * $discountRatio, 2);
    }

    /**
     * @param Cart $cart
     */
    public function calculateCutOfTaxTotal(Cart $cart)
    {
        $discountRatio = $cart->getDiscountRatio();

        return round($this->getPriceAfterDiscounts($cart->getApplicableDiscounts()) * $discountRatio, 2);
    }

    /**
     * @param Cart $cart
     */
    public function calculateCutOfShippingTotal(Cart $cart)
    {

    }

    /**
     * @param array $applicableDiscounts
     * @return array
     */
    public function toArray(array $applicableDiscounts = [])
    {
        return array_merge(
            [
                'product' => $this->product,
                'quantity' => $this->quantity,
                'priceBeforeDiscounts' => $this->getPriceBeforeDiscounts(),
                'priceAfterDiscounts' => $this->getPriceAfterDiscounts($applicableDiscounts),
            ]
        );
    }

    /**
     * @param $array
     */
    public function fromArray($array)
    {
        $this->id = $array['id'];
        $this->product = new Product($array['product']);
        $this->quantity = $array['quantity'];
    }
}