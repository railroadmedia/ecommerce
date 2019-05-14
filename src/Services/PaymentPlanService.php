<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Subscription;

class PaymentPlanService
{
    /**
     * @var \Railroad\Ecommerce\Services\CartService
     */
    private $cartService;

    /**
     * PaymentPlanService constructor.
     *
     * @param CartService $cartService
     */
    public function __construct(
        CartService $cartService
    )
    {
        $this->cartService = $cartService;
    }

    /**
     * Check if payment plan it's eligible: the order should not contain subscription product and
     * totaling over a set amount (config payment_plan_minimum_price)
     *
     * @return bool
     */
    public function isPaymentPlanEligible()
    {
        $cart = $this->cartService->getCart();

        if ((!$this->hasSubscriptionItems($cart->getItems())) &&
            (($cart->getTotalDue() - $cart->calculateTaxesDue() - $cart->calculateShippingDue()) >
                config('ecommerce.payment_plan_minimum_price'))) {
            return true;
        }

        return false;
    }

    /**
     * Check if in the cart exists subscription products
     *
     * @param array $cartItems
     * @return bool
     */
    public function hasSubscriptionItems($cartItems)
    {
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getProduct()
                    ->getType() == Subscription::TYPE_SUBSCRIPTION) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getPaymentPlanPricingForCartItems()
    {
        // todo - refactor
        $paymentPlanPricing = [];
        if ($this->isPaymentPlanEligible()) {
            $initialPaymentPlanOption =
                $this->cartService->getCart()
                    ->getPaymentPlanNumberOfPayments();
            foreach (config('ecommerce.payment_plan_options') as $paymentPlan) {
                $this->cartService->setPaymentPlanNumberOfPayments($paymentPlan);

                $paymentPlanPricing[$paymentPlan] =
                    $this->cartService->getCart()
                        ->calculatePricePerPayment();
            }
            $this->cartService->setPaymentPlanNumberOfPayments($initialPaymentPlanOption);
        }

        return $paymentPlanPricing;
    }
}