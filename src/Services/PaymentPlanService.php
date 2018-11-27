<?php

namespace Railroad\Ecommerce\Services;

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
    ) {
        $this->cartService = $cartService;
    }

    /** Check if payment plan it's eligible: the order should not contain subscription product and
     *  totaling over a set amount (config paymentPlanMinimumPrice)
     *
     * @param array $cartItems
     * @return bool
     */
    public function isPaymentPlanEligible()
    {
        $cart = $this->cartService->getCart();

        if ((!$this->hasSubscriptionItems($cart->getItems())) &&
            (($cart->getTotalDue() - $cart->calculateTaxesDue() - $cart->calculateShippingDue()) >
                config('ecommerce.paymentPlanMinimumPrice'))) {
            return true;
        }

        return false;
    }

    /** Check if in the cart exists subscription products
     *
     * @param array $cartItems
     * @return bool
     */
    public function hasSubscriptionItems($cartItems)
    {
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getProduct()['type'] == ConfigService::$typeSubscription) {
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
        $paymentPlanPricing = [];
        if ($this->isPaymentPlanEligible()) {
            $initialPaymentPlanOption =
                $this->cartService->getCart()
                    ->getPaymentPlanNumberOfPayments();
            foreach (config('ecommerce.paymentPlanOptions') as $paymentPlan) {
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