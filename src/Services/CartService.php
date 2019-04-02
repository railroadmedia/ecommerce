<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Permissions\Services\PermissionService;

class CartService
{
    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var DiscountCriteriaService
     */
    private $discountCriteriaService;

    /**
     * @var DiscountRepository
     */
    private $discountRepository;

    /**
     * @var DiscountService
     */
    private $discountService;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ShippingOptionRepository
     */
    private $shippingOptionRepository;

    /**
     * @var Store
     */
    private $session;

    /**
     * @var TaxService
     */
    private $taxService;

    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';
    const PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY = 'payment-plan-number-of-payments';
    const PAYMENT_PLAN_LOCKED_SESSION_KEY = 'order-form-payment-plan-locked';
    const PROMO_CODE_KEY = 'promo-code';

    /**
     * @var Cart
     */
    private $cart;

    /**
     * CartService constructor.
     *
     * @param CartAddressService $cartAddressService
     * @param DiscountCriteriaService $discountCriteriaService
     * @param DiscountRepository $discountRepository
     * @param DiscountService $discountService
     * @param EcommerceEntityManager $entityManager
     * @param PermissionService $permissionService
     * @param ProductRepository $productRepository
     * @param ShippingOptionRepository $shippingOptionRepository
     * @param Store $session
     * @param TaxService $taxService
     */
    public function __construct(
        CartAddressService $cartAddressService,
        DiscountCriteriaService $discountCriteriaService,
        DiscountRepository $discountRepository,
        DiscountService $discountService,
        EcommerceEntityManager $entityManager,
        PermissionService $permissionService,
        ProductRepository $productRepository,
        ShippingOptionRepository $shippingOptionRepository,
        Store $session,
        TaxService $taxService
    ) {

        $this->cartAddressService = $cartAddressService;
        $this->discountCriteriaService = $discountCriteriaService;
        $this->discountService = $discountService;
        $this->discountRepository = $discountRepository;
        $this->entityManager = $entityManager;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
        $this->shippingOptionRepository = $shippingOptionRepository;
        $this->session = $session;
        $this->taxService = $taxService;
    }

    public function refreshCart()
    {
        $this->cart = Cart::fromSession();
    }

    /**
     * @return bool
     */
    public function cartHasAnyPhysicalItems()
    {
        $products = $this->productRepository->findBySkus(['sku' => $this->cart->listSkus()]);

        foreach ($products as $product) {
            if ($product->getIsPhysical()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return float
     */
    public function getTotalCartItemWeight()
    {
        $products = $this->productRepository->findBySkus(['sku' => $this->cart->listSkus()]);

        $totalWeight = 0;

        foreach ($products as $product) {
            $totalWeight += $product->getWeight() ?? 0;
        }

        return $totalWeight;
    }

    /**
     * @return float
     */
    public function getTotalShippingDue()
    {
        $shippingAddress = $this->cart->getShippingAddress();
        $shippingCountry = $shippingAddress ? $shippingAddress->getCountry() : '';

        $totalWeight = $this->getTotalCartItemWeight();

        $shippingOption = $this->shippingOptionRepository->getShippingCosts($shippingCountry, $totalWeight);

        $totalShippingCost = 0;

        if (!empty($shippingOption)) {
            $shippingCost = $shippingOption->getShippingCostsWeightRanges()
                ->first();

            $totalShippingCost = $shippingCost->getPrice();
        }

        // apply any shipping discounts here

        return $totalShippingCost;
    }

    /**
     * @param $amountToTax
     * @return float
     */
    public function getTotalTaxDue($amountToTax)
    {
        // todo: calculate tax costs from tax service?

        return 0;
    }

    /**
     * @return float
     */
    public function getTotalDue()
    {
        $products = $this->productRepository->findBySkus(['sku' => $this->cart->listSkus()]);

        $totalItemCostDue = 0;

        foreach ($products as $product) {
            $totalItemCostDue += $product->getPrice() ?? 0;
        }

        // todo: apply product discounts and subtract from $totalItemCostDue

        $shippingDue = $this->getTotalShippingDue();

        // only item and shipping costs are taxed
        $taxDue = $this->getTotalTaxDue($totalItemCostDue + $shippingDue);

        if ($this->cart->getPaymentPlanNumberOfPayments() > 1) {
            $financeDue = config('ecommerce.financing_cost_per_order');
        } else {
            $financeDue = 0;
        }

        return $totalItemCostDue + $shippingDue + $taxDue + $financeDue;
    }

    /**
     * @return float
     */
    public function getDueForInitialPayment()
    {
        $products = $this->productRepository->findBySkus(['sku' => $this->cart->listSkus()]);

        $shippingDue = $this->getTotalShippingDue();

        $totalItemCostDue = 0;

        foreach ($products as $product) {
            $totalItemCostDue += $product->getPrice() ?? 0;
        }

        // todo: apply product discounts and subtract from $totalItemCostDue

        // only item and shipping costs are taxed
        $taxDue = $this->getTotalTaxDue($totalItemCostDue + $shippingDue);

        if ($this->cart->getPaymentPlanNumberOfPayments() > 1) {
            $financeDue = config('ecommerce.financing_cost_per_order');
        } else {
            $financeDue = 0;
        }

        // Customers can only finance the order item price, taxes, and finance.
        // All shipping must be paid on the first payment.
        $totalToFinance = $totalItemCostDue + $taxDue + $financeDue;

        $initialTotalDueBeforeShipping = round($totalToFinance / $this->cart->getPaymentPlanNumberOfPayments(), 2);

        // account for any rounded off cents by adding the difference after all payments to the first payment
        if ($initialTotalDueBeforeShipping * $this->cart->getPaymentPlanNumberOfPayments() != $totalToFinance) {
            $initialTotalDueBeforeShipping += abs($initialTotalDueBeforeShipping *
                $this->cart->getPaymentPlanNumberOfPayments() - $totalToFinance);
        }

        return $initialTotalDueBeforeShipping + $shippingDue;
    }

    /**
     * Return an array with the discounts that should be applied, the discounts criteria are met
     *
     * @return array
     */
    public function getDiscountsToApply()
    {
        // todo: this should all be moved to the discount service
        // return $this->discountService->getDiscountsForCart($this->cart);

        $discountsToApply = [];

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->discountRepository->createQueryBuilder('d');

        $qb->select(['d', 'dc'])
            ->leftJoin('d.discountCriterias', 'dc')
            ->where($qb->expr()
                ->eq('d.active', ':active'))
            ->setParameter('active', true);

        $activeDiscounts = $qb->getQuery()
            ->getResult();

        foreach ($activeDiscounts as $activeDiscount) {
            /**
             * @var $activeDiscount \Railroad\Ecommerce\Entities\Discount
             */
            $criteriaMet = false;

            foreach ($activeDiscount->getDiscountCriterias() as $discountCriteria) {
                /**
                 * @var $discountCriteria \Railroad\Ecommerce\Entities\DiscountCriteria
                 */
                $discountCriteriaMet =
                    $this->discountCriteriaService->discountCriteriaMetForOrder($this->getCart(), $discountCriteria,
                        $this->getPromoCode());

                if ($discountCriteriaMet) {
                    $criteriaMet = true;
                    break;
                }
            }

            if ($criteriaMet) {
                $discountsToApply[$activeDiscount->getId()] = $activeDiscount;
            }
        }

        return $discountsToApply;
    }

    /**
     * Calculate the discounted price on items and the discounted amount on cart(discounts that should be applied on
     * order) and set the discounted price on order item and the total discount amount on cart.
     *
     * Return the cart with the discounts applied.
     *
     * @return Cart
     */
    //    public function applyDiscounts()
    //    {
    //        foreach (
    //            $this->getCart()
    //                ->getDiscounts() as $discount
    //        ) {
    //            /**
    //             * @var $discount \Railroad\Ecommerce\Entities\Discount
    //             */
    //            foreach (
    //                $this->getCart()
    //                    ->getItems() as $index => $item
    //            ) {
    //                /**
    //                 * @var $item \Railroad\Ecommerce\Entities\Structures\CartItem
    //                 */
    //
    //                /**
    //                 * @var $cartProduct \Railroad\Ecommerce\Entities\Product
    //                 */
    //                $cartProduct = $item->getProduct();
    //
    //                /**
    //                 * @var $discountProduct \Railroad\Ecommerce\Entities\Product
    //                 */
    //                $discountProduct = $discount->getProduct();
    //
    //                if ($cartProduct &&
    //                    (($discountProduct && $cartProduct->getId() == $discountProduct->getId()) ||
    //                        $cartProduct->getCategory() == $discount->getProductCategory())) {
    //                    $productDiscount = 0;
    //
    //                    if ($discount->getType() == DiscountService::PRODUCT_AMOUNT_OFF_TYPE) {
    //
    //                        $productDiscount = $discount->getAmount() * $item->getQuantity();
    //                    }
    //
    //                    if ($discount->getType() == DiscountService::PRODUCT_PERCENT_OFF_TYPE) {
    //                        $productDiscount = $discount->getAmount() / 100 * $item->getPrice() * $item->getQuantity();
    //                    }
    //
    //                    if ($discount->getType() == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
    //                        $productDiscount = $discount->getAmount() * $item->getQuantity();
    //                    }
    //
    //                    $discountedPrice = round($item->getTotalPrice() - $productDiscount, 2);
    //
    //                    /**
    //                     * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
    //                     */
    //                    $cartItem = $this->getCart()
    //                        ->getItems()[$index];
    //
    //                    $cartItem->setDiscountedPrice(max($discountedPrice, 0));
    //                }
    //            }
    //        }
    //
    //        $discountedAmount = $this->discountService->getAmountDiscounted($this->getCart()
    //            ->getDiscounts(), $this->getCart()
    //            ->getTotalDue());
    //
    //        $this->getCart()
    //            ->setTotalDiscountAmount($discountedAmount);
    //
    //        return $this->getCart();
    //    }
    //
    //    public function setBrand($brand)
    //    {
    //        $this->cart->setBrand($brand);
    //    }
}
