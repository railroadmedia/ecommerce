<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Illuminate\Support\Facades\Session;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\TaxService;

class Cart
{
    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';
    const PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY = 'payment-plan-number-of-payments';
    const PAYMENT_PLAN_LOCKED_SESSION_KEY = 'order-form-payment-plan-locked';

    public $items;
    private $shippingCosts;
    private $totalTax;
    private $totalDue;
    private $discounts;
    private $totalDiscountAmount;
    public $appliedDiscounts;
    private $brand;

    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * CartService constructor.
     *
     * @param CartAddressService $cartAddressService
     * @param TaxService $taxService
     */
    public function __construct(
        CartAddressService $cartAddressService,
        TaxService $taxService
    ) {
        $this->cartAddressService = $cartAddressService;
        $this->taxService = $taxService;
    }

    /**
     * Get cart items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items ?? [];
    }

    /**
     * Add cart on session
     *
     * @param $cartItem
     * @return Cart
     */
    public function addCartItem($cartItem)
    {
        $this->items[] = $cartItem;
        $this->totalDue = $this->getTotalDue();
        $this->discounts = $this->getDiscounts();
        $this->shippingCosts = $this->calculateShippingDue();
        $this->totalTax = $this->calculateTaxesDue();
        $this->brand = $this->getBrand();

        Session::put($this->getBrand() . '-' . self::SESSION_KEY, $this);

        return $this;
    }

    /**
     * @param $taxesDue
     */
    public function setTaxesDue($taxesDue)
    {
        $this->totalTax = $taxesDue;
    }

    /**
     * Calculate taxes based on items, shipping costs and tax rate
     *
     * @return mixed
     */
    public function calculateTaxesDue()
    {
        /**
         * @var $billingAddress \Railroad\Ecommerce\Entities\Structures\Address
         */
        $billingAddress = $this->cartAddressService
                                ->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        if ($billingAddress) {

            $taxRate = $this->taxService->getTaxRate($billingAddress);

            $this->totalTax =
                round(
                    (
                        max($this->getTotalDueForItems() * $taxRate, 0) +
                        max($this->shippingCosts * $taxRate, 0)
                    ),
                    2
                );
        }

        return max((float)($this->totalTax), 0);
    }

    /**
     * Calculate total due
     *
     * @return float
     */
    public function getTotalDue()
    {
        $financeCharge = ($this->getPaymentPlanNumberOfPayments() > 1) ? 1 : 0;

        $totalDueFromItems = $this->getTotalDueForItems();

        return round(
            $totalDueFromItems -
            $this->getTotalDiscountAmount() +
            $this->calculateTaxesDue() +
            $this->calculateShippingDue() +
            $financeCharge,
            2
        );
    }

    /**
     * Calculate price per payment
     *
     * @return float
     */
    public function calculatePricePerPayment()
    {
        if ($this->getPaymentPlanNumberOfPayments() > 1) {
            /*
             * All shipping should always be paid in the first payment.
             */
            return round(
                (($this->getTotalDue() - $this->calculateShippingDue()) / $this->getPaymentPlanNumberOfPayments()),
                2
            );
        }

        return $this->getTotalDue();
    }

    /**
     * Get payment plan selected option from the session
     *
     * @return mixed
     */
    public function getPaymentPlanNumberOfPayments()
    {
        if (Session::has(self::PAYMENT_PLAN_LOCKED_SESSION_KEY) &&
            Session::get(self::PAYMENT_PLAN_LOCKED_SESSION_KEY) > 0) {
            return Session::get(self::PAYMENT_PLAN_LOCKED_SESSION_KEY, 1);
        }

        return Session::get(self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY, 1);
    }

    /**
     * @param $shipping
     *
     * @return $this
     */
    public function setShippingCosts($shipping)
    {
        $this->shippingCosts = $shipping;

        return $this;
    }

    /**
     * @return float
     */
    public function getShippingCosts()
    {
        return $this->shippingCosts;
    }

    /**
     * @param bool $applyDiscounts
     *
     * @return float
     */
    public function calculateShippingDue($applyDiscounts = true)
    {
        $amountDiscounted = 0;

        if ($applyDiscounts) {
            foreach ($this->getDiscounts() as $discount) {
                /**
                 * @var $discount \Railroad\Ecommerce\Entities\Discount
                 */
                if ($discount->getType() == DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE) {
                    $amountDiscounted = round($amountDiscounted + $discount->getAmount(), 2);
                } elseif ($discount->getType() == DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE) {
                    $amountDiscounted = round($amountDiscounted + $discount->getAmount() / 100 * $this->shippingCosts, 2);
                } elseif ($discount->getType() == DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {
                    return $discount->getAmount();
                }
            }
        }

        return max((float)($this->shippingCosts - $amountDiscounted), 0);
    }

    /**
     * @return array - of \Railroad\Ecommerce\Entities\Discount
     */
    public function getDiscounts()
    {
        return $this->discounts ?? [];
    }

    /**
     * @param array $discounts - array of \Railroad\Ecommerce\Entities\Discount
     */
    public function setDiscounts(array $discounts)
    {
        $this->discounts = $discounts;
    }

    /**
     * @return float|int
     */
    public function getTotalWeight()
    {
        $weight = 0.0;

        foreach ($this->getItems() as $cartItem) {
            /**
             * @var $product \Railroad\Ecommerce\Entities\Product
             */
            $product = $cartItem->getProduct();
            $weight += $product->getWeight() * $cartItem->getQuantity();
        }

        return $weight;
    }

    /**
     * @return float
     */
    public function calculateInitialPricePerPayment()
    {
        if ($this->getPaymentPlanNumberOfPayments() > 1) {
            /*
             * We need to make sure we add any rounded off $$ back to the first payment.
             */
            $roundingFirstPaymentAdjustment =
                ($this->calculatePricePerPayment() * $this->getPaymentPlanNumberOfPayments()) -
                ($this->getTotalDue() - $this->calculateShippingDue());

            return round(
                $this->calculatePricePerPayment() - $roundingFirstPaymentAdjustment + $this->calculateShippingDue(),
                2
            );
        }

        return $this->calculatePricePerPayment();
    }

    /**
     * @return mixed
     */
    public function calculateCartItemsSubTotalAfterDiscounts()
    {
        return max((float)($this->totalDue - $this->totalDiscountAmount + $this->shippingCosts + $this->totalTax), 0);
    }

    /**
     * @param $discount
     */
    public function setTotalDiscountAmount($discount)
    {
        $this->totalDiscountAmount = $discount;
    }

    /**
     * @return mixed
     */
    public function getTotalDiscountAmount()
    {
        return $this->totalDiscountAmount;
    }

    /**
     * @return array - array of \Railroad\Ecommerce\Entities\Discount
     */
    public function getAppliedDiscounts()
    {
        return $this->appliedDiscounts ?? [];
    }

    /**
     * @param array $discounts - array of \Railroad\Ecommerce\Entities\Discount
     */
    public function setAppliedDiscounts(array $discounts)
    {
        $this->appliedDiscounts = $discounts;
    }

    /**
     * @return int
     */
    public function getTotalDueForItems()
    {
        $totalDueFromItems = 0;

        foreach ($this->getItems() as $cartItem) {

            $totalDueFromItems += ($cartItem->getDiscountedPrice()) ?
                                    $cartItem->getDiscountedPrice() :
                                    $cartItem->getTotalPrice();
        }

        return $totalDueFromItems;
    }

    public function getTotalInitial()
    {
        $totalInitial = 0;

        foreach ($this->getItems() as $cartItem) {

            /**
             * @var $product \Railroad\Ecommerce\Entities\Product
             */
            $product = $cartItem->getProduct();
            $totalInitial += $product->getPrice() * $cartItem->getQuantity();
        }

        return $totalInitial;
    }

    /**
     * Remove discounts from the session and reset the total discount amount
     */
    public function removeAppliedDiscount()
    {
        $this->appliedDiscounts = [];
        $this->discounts = [];
        $this->totalDiscountAmount = 0;
    }

    /**
     * Set brand on the cart
     *
     * @param $brand
     * @return $this
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Get brand
     *
     * @return string
     */
    public function getBrand()
    {
        return $this->brand ?? ConfigService::$brand;
    }
}