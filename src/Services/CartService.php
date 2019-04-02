<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
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

        $this->cart = new Cart(
            $this->cartAddressService,
            $this->taxService
        );
    }

    /**
     * Merges the discounts and products entities into entity manager
     */
    public function mergeEntities()
    {
        $mergedDiscounts = [];

        foreach ($this->getCart()->getDiscounts() as $discount) {
            /**
             * @var $discount \Railroad\Ecommerce\Entities\Discount
             */
            $mergedDiscounts[] = $this->entityManager->merge($discount);
        }

        $this->cart->setDiscounts($mergedDiscounts);

        foreach ($this->getCart()->getItems() as $cartItem) {
            /**
             * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
             */

            /**
             * @var $mergedProduct \Railroad\Ecommerce\Entities\Product
             */
            $mergedProduct = $this->entityManager->merge($cartItem->getProduct());

            $cartItem->setProduct($mergedProduct);
        }
    }

    /**
     * Detach the discounts and products entities from entity manager
     */
    public function detachEntities()
    {
        foreach ($this->getCart()->getDiscounts() as $discount) {
            /**
             * @var $discount \Railroad\Ecommerce\Entities\Discount
             */
            $this->entityManager->detach($discount);
        }

        foreach ($this->getCart()->getItems() as $cartItem) {
            /**
             * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            $this->entityManager->detach($cartItem->getProduct());
        }
    }

    /**
     * Return an array with the cart items.
     *
     * @return CartItem[]
     */
    public function getAllCartItems()
    {
        return $this->getCart()->getItems();
    }

    /**
     * @return bool
     */
    public function requiresShipping()
    {
        foreach ($this->getCart()->getItems() as $cartItem) {
            /**
             * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            if ($cartItem->getRequiresShippingAddress()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear the cart items
     */
    public function removeAllCartItems()
    {
        foreach ($this->session->all() as $sessionKey => $sessionValue) {
            if (
                substr(
                    $sessionKey,
                    0,
                    strlen(ConfigService::$brand . '-' . self::SESSION_KEY)
                ) == ConfigService::$brand . '-' . self::SESSION_KEY
            ) {
                $this->session->remove($sessionKey);
            }
        }
    }

    /**
     * Clear and lock the cart
     */
    public function lockCart()
    {
        $this->removeAllCartItems();
        $this->setPromoCode(null);

        $this->session->put(
            ConfigService::$brand . '-' . self::LOCKED_SESSION_KEY, true
        );
    }

    /**
     * Check if the cart it's in locked state
     *
     * @return bool
     */
    public function isLocked()
    {
        return true == $this->session->get(
            ConfigService::$brand . '-' . self::LOCKED_SESSION_KEY
        );
    }

    /**
     * Clear and unlock the cart
     */
    public function unlockCart()
    {
        $this->removeAllCartItems();
        $this->unlockPaymentPlan();

        $this->session->put(
            ConfigService::$brand . '-' . self::LOCKED_SESSION_KEY,
            false
        );
    }

    /**
     * Remove the cart item
     *
     * @param $id
     *
     * @return array
     */
    public function removeCartItem($id)
    {
        $items = $this->getCart()->getItems();
        $index = array_search($id, array_pluck($items, 'id'));
        unset($items[$index]);
        $this->removeAllCartItems();

        foreach ($items as $item) {
            /**
             * @var $item \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            $this->addCartItem(
                $item->getName(),
                $item->getDescription(),
                $item->getQuantity(),
                $item->getPrice(),
                $item->getRequiresShippingAddress(),
                $item->getRequiresBillingAddress(),
                $item->getSubscriptionIntervalType(),
                $item->getSubscriptionIntervalCount(),
                $item->getOptions()
            );
        }

        return $this->cart->getItems();
    }

    /**
     * Update cart item quantity and total price.
     *
     * @param $cartItemId
     * @param $quantity
     *
     * @return array
     */
    public function updateCartItemQuantity($cartItemId, $quantity)
    {
        $items = $this->getCart()->getItems();
        $index = array_search($cartItemId, array_pluck($items, 'id'));

        $cartItem = $this->getCartItem($cartItemId);

        $cartItem->setQuantity($quantity);
        $cartItem->setTotalPrice($quantity * $cartItem->getPrice());
        $items[$index] = $cartItem;
        $this->removeAllCartItems();

        foreach ($items as $item) {
            /**
             * @var $item \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            $this->addCartItem(
                $item->getName(),
                $item->getDescription(),
                $item->getQuantity(),
                $item->getPrice(),
                $item->getRequiresShippingAddress(),
                $item->getRequiresBillingAddress(),
                $item->getSubscriptionIntervalType(),
                $item->getSubscriptionIntervalCount(),
                $item->getOptions()
            );
        }

        return $this->cart->getItems();
    }

    /**
     * Get a cart item from the session based on cart item id
     *
     * @param $id
     * @return mixed|null
     */
    public function getCartItem($id)
    {
        $items = $this->getCart()->getItems();
        $index = array_search($id, array_pluck($items, 'id'));

        return $items[$index];
    }

    /**
     * Set on the session the number of payments
     *
     * @param $numberOfPayments
     */
    public function setPaymentPlanNumberOfPayments($numberOfPayments)
    {
        if (empty($numberOfPayments) || $numberOfPayments == 1) {
            $this->session->put(
                self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY,
                1
            );
        } else {
            $this->session->put(
                self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY,
                $numberOfPayments
            );
        }
    }

    /**
     * Lock payment plan
     *
     * @param $numberOfPaymentsToForce
     */
    public function lockPaymentPlan($numberOfPaymentsToForce)
    {
        $this->session->put(
            self::PAYMENT_PLAN_LOCKED_SESSION_KEY,
            $numberOfPaymentsToForce
        );
    }

    /**
     * Unlock payment plan
     */
    public function unlockPaymentPlan()
    {
        $this->session->remove(self::PAYMENT_PLAN_LOCKED_SESSION_KEY);
    }

    /**
     * Set promo code on the session
     *
     * @param string $promoCode
     */
    public function setPromoCode(?string $promoCode)
    {
        $this->session->put(self::PROMO_CODE_KEY, $promoCode);
    }

    /**
     * Get promo code from the session
     *
     * @return mixed
     */
    public function getPromoCode()
    {
        return $this->session->get(self::PROMO_CODE_KEY);
    }

    /**
     * Add item to cart, calculate the shipping costs based on cart's products, apply discounts on cart items
     *
     * @param $name
     * @param $description
     * @param $quantity
     * @param $price
     * @param $requiresShippingAddress
     * @param $requiresBillingAddress
     * @param null $subscriptionIntervalType
     * @param null $subscriptionIntervalCount
     * @param array $options
     * @param string $customBrand
     *
     * @return Cart
     */
    public function addCartItem(
        $name,
        $description,
        $quantity,
        $price,
        $requiresShippingAddress,
        $requiresBillingAddress,
        $subscriptionIntervalType = null,
        $subscriptionIntervalCount = null,
        $options = [],
        $customBrand = null
    ) {
        $brand = ConfigService::$brand;

        if (
            $this->permissionService->can(
                auth()->id(),
                'place-orders-for-other-users'
            )
        ) {
            $brand = $customBrand ?? ConfigService::$brand;
        }

        $this->getCart()->setBrand($brand);

        /**
         * @var $cart \Railroad\Ecommerce\Entities\Structures\Cart
         */
        $cart = $this->getCart();

        /**
         * @var $product \Railroad\Ecommerce\Entities\Product
         */
        $product = $this->productRepository->find($options['product-id']);

        // If the item already exists, just increase the quantity
        foreach ($cart->getItems() as $cartItem) {
            /**
             * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            if (
                !empty($cartItem->getOptions()['product-id']) &&
                $cartItem->getOptions()['product-id'] == $options['product-id']
            ) {
                $cartItem->setQuantity($cartItem->getQuantity() + $quantity);

                $totalPrice = $cartItem->getTotalPrice() +
                                $quantity * $cartItem->getPrice();

                $cartItem->setTotalPrice($totalPrice);

                /**
                 * @var $discountsToApply array - of \Railroad\Ecommerce\Entities\Discount
                 */
                $discountsToApply = $this->getDiscountsToApply();

                $this->discountService->applyDiscounts(
                    $discountsToApply,
                    $cart
                );

                $cart->setDiscounts($discountsToApply);

                $cartDiscounted = $this->applyDiscounts();

                // $this->detachEntities(); // todo - enable for testing

                $this->session->put(
                    $brand . '-' . self::SESSION_KEY,
                    $cartDiscounted
                );

                return $cartDiscounted;
            }
        }

        $cartItem = new CartItem();
        $cartItem->setId(bin2hex(openssl_random_pseudo_bytes(32)));
        $cartItem->setName($name);
        $cartItem->setDescription($description);
        $cartItem->setQuantity($quantity);
        $cartItem->setPrice($price);
        $cartItem->setTotalPrice(round($quantity * $price, 2));
        $cartItem->setRequiresShippingAddress($requiresShippingAddress);
        $cartItem->setRequiresBillingAddress($requiresBillingAddress);
        $cartItem->setSubscriptionIntervalType($subscriptionIntervalType);
        $cartItem->setSubscriptionIntervalCount($subscriptionIntervalCount);
        $cartItem->setProduct($product);
        $cartItem->setOptions($options);

        $cart->addCartItem($cartItem);

        $this->calculateShippingCosts();

        foreach ($cart->getItems() as $cartItem) {
            /**
             * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            $cartItem->removeAppliedDiscounts();
        }

        /**
         * @var $discountsToApply array - of \Railroad\Ecommerce\Entities\Discount
         */
        $discountsToApply = $this->getDiscountsToApply();

        $this->discountService->applyDiscounts(
            $discountsToApply,
            $cart
        );

        $cart->setDiscounts($discountsToApply);

        $cartDiscounted = $this->applyDiscounts();

        $this->session->put($brand . '-' . self::SESSION_KEY, $cartDiscounted);

        return $cartDiscounted;
    }

    public function calculateShippingCosts()
    {
        $cart = $this->getCart();

        /**
         * @var $shippingAddress \Railroad\Ecommerce\Entities\Structures\Address
         */
        $shippingAddress = $this->cartAddressService
                                ->getAddress(
                                    CartAddressService::SHIPPING_ADDRESS_TYPE
                                );

        $shippingCountry = $shippingAddress ?
                                $shippingAddress->getCountry() : '';

        $totalWeight = $cart->getTotalWeight();

        /**
         * @var $shippingCosts array - of \Railroad\Ecommerce\Entities\ShippingOption
         */
        $shippingOptions = $this->shippingOptionRepository
                                ->getShippingCosts(
                                    $shippingCountry,
                                    $totalWeight
                                );
        $shippingCosts = 0;

        if (count($shippingOptions)) {
            /**
             * @var $shippingOption \Railroad\Ecommerce\Entities\ShippingOption
             */
            $shippingOption = $shippingOptions[0];
            /**
             * @var $shippingCost \Railroad\Ecommerce\Entities\ShippingCostsWeightRange
             */
            $shippingCost = $shippingOption
                                ->getShippingCostsWeightRanges()
                                ->first();

            $shippingCosts = $shippingCost->getPrice();
        }

        $cart->setShippingCosts($shippingCosts);
    }

    /**
     * Get the cart entity from the session. If the cart it's not set on the session an empty cart it's returned.
     *
     * @return Cart
     */
    public function getCart()
    {
        foreach ($this->session->all() as $sessionKey => $sessionValue) {
            if (
                substr(
                    $sessionKey,
                    0,
                    strlen($this->cart->getBrand() . '-' . self::SESSION_KEY)
                ) == $this->cart->getBrand() . '-' . self::SESSION_KEY
            ) {
                return $sessionValue;
            }
        }

        return new Cart(
            $this->cartAddressService,
            $this->taxService
        );
    }

    /**
     * Return an array with the discounts that should be applied, the discounts criteria are met
     *
     * @return array
     */
    public function getDiscountsToApply()
    {
        $discountsToApply = [];

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->discountRepository->createQueryBuilder('d');

        $qb
            ->select(['d', 'dc'])
            ->leftJoin('d.discountCriterias', 'dc')
            ->where($qb->expr()->eq('d.active', ':active'))
            ->setParameter('active', true);

        $activeDiscounts = $qb->getQuery()->getResult();

        foreach ($activeDiscounts as $activeDiscount) {
            /**
             * @var $activeDiscount \Railroad\Ecommerce\Entities\Discount
             */
            $criteriaMet = false;
            foreach ($activeDiscount->getDiscountCriterias() as $discountCriteria) {
                /**
                 * @var $discountCriteria \Railroad\Ecommerce\Entities\DiscountCriteria
                 */
                $discountCriteriaMet = $this->discountCriteriaService
                                            ->discountCriteriaMetForOrder(
                                                $this->getCart(),
                                                $discountCriteria,
                                                $this->getPromoCode()
                                            );

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
     * Calculate the discounted price on items and the discounted amount on cart(discounts that should be applied on order)
     * and set the discounted price on order item and the total discount amount on cart.
     *
     * Return the cart with the discounts applied.
     *
     * @return Cart
     */
    public function applyDiscounts()
    {
        foreach ($this->getCart()->getDiscounts() as $discount) {
            /**
             * @var $discount \Railroad\Ecommerce\Entities\Discount
             */
            foreach ($this->getCart()->getItems() as $index => $item) {
                /**
                 * @var $item \Railroad\Ecommerce\Entities\Structures\CartItem
                 */

                /**
                 * @var $cartProduct \Railroad\Ecommerce\Entities\Product
                 */
                $cartProduct = $item->getProduct();

                /**
                 * @var $discountProduct \Railroad\Ecommerce\Entities\Product
                 */
                $discountProduct = $discount->getProduct();

                if (
                    $cartProduct &&
                    (
                        (
                            $discountProduct &&
                            $cartProduct->getId() == $discountProduct->getId()
                        )
                        || $cartProduct->getCategory() == $discount->getProductCategory()
                    )
                ) {
                    $productDiscount = 0;

                    if ($discount->getType() == DiscountService::PRODUCT_AMOUNT_OFF_TYPE) {

                        $productDiscount = $discount->getAmount() * $item->getQuantity();
                    }

                    if ($discount->getType() == DiscountService::PRODUCT_PERCENT_OFF_TYPE) {
                        $productDiscount = $discount->getAmount() / 100 * $item->getPrice() * $item->getQuantity();
                    }

                    if ($discount->getType() == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                        $productDiscount = $discount->getAmount() * $item->getQuantity();
                    }

                    $discountedPrice = round($item->getTotalPrice() - $productDiscount, 2);

                    /**
                     * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
                     */
                    $cartItem = $this->getCart()->getItems()[$index];

                    $cartItem->setDiscountedPrice(max($discountedPrice, 0));
                }
            }
        }

        $discountedAmount = $this->discountService->getAmountDiscounted(
            $this->getCart()->getDiscounts(),
            $this->getCart()->getTotalDue()
        );

        $this->getCart()->setTotalDiscountAmount($discountedAmount);

        return $this->getCart();
    }

    public function setBrand($brand)
    {
        $this->cart->setBrand($brand);
    }
}
