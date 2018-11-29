<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Cart;
use Railroad\Ecommerce\Entities\CartItem;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;

class CartService
{
    private $session;

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var DiscountRepository
     */
    private $discountRepository;

    /**
     * @var ShippingOptionRepository
     */
    private $shippingOptionsRepository;

    /**
     * @var DiscountCriteriaService
     */
    private $discountCriteriaService;

    /**
     * @var DiscountService
     */
    private $discountService;

    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';
    const PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY = 'payment-plan-number-of-payments';
    const PAYMENT_PLAN_LOCKED_SESSION_KEY = 'order-form-payment-plan-locked';
    const PROMO_CODE_KEY = 'promo-code';

    private $cart;

    /**
     * CartService constructor.
     *
     * @param Store $session
     * @param ProductRepository $productRepository
     */
    public function __construct(
        Store $session,
        ProductRepository $productRepository,
        DiscountRepository $discountRepository,
        CartAddressService $cartAddressService,
        DiscountCriteriaService $discountCriteriaService,
        DiscountService $discountService,
        ShippingOptionRepository $shippingOptionRepository
    ) {
        $this->session = $session;
        $this->productRepository = $productRepository;
        $this->discountRepository = $discountRepository;
        $this->cartAddressService = $cartAddressService;
        $this->discountCriteriaService = $discountCriteriaService;
        $this->discountService = $discountService;
        $this->shippingOptionsRepository = $shippingOptionRepository;
        $this->cart = new Cart();
    }

    /** Return an array with the cart items.
     *
     * @return array
     */
    public function getAllCartItems()
    {
        return $this->cart->getItems();
    }

    /** Clear the cart items
     */
    public function removeAllCartItems()
    {
        foreach ($this->session->all() as $sessionKey => $sessionValue) {
            if (substr($sessionKey, 0, strlen(ConfigService::$brand . '-' . self::SESSION_KEY)) ==
                ConfigService::$brand . '-' . self::SESSION_KEY) {
                $this->session->remove($sessionKey);
            }
        }
    }

    /** Clear and lock the cart
     */
    public function lockCart()
    {
        $this->removeAllCartItems();

        $this->session->put(ConfigService::$brand . '-' . self::LOCKED_SESSION_KEY, true);
    }

    /** Check if the cart it's in locked state
     *
     * @return bool
     */
    public function isLocked()
    {
        return $this->session->get(ConfigService::$brand . '-' . self::LOCKED_SESSION_KEY) == true;
    }

    /**
     * Clear and unlock the cart
     */
    public function unlockCart()
    {
        $this->removeAllCartItems();
        $this->unlockPaymentPlan();

        $this->session->put(ConfigService::$brand . '-' . self::LOCKED_SESSION_KEY, false);
    }

    /** Remove the cart item
     *
     * @param $id
     */
    public function removeCartItem($id)
    {
        $items =
            $this->getCart()
                ->getItems();
        $index = array_search($id, array_pluck($items, 'id'));
        unset($items[$index]);
        $this->removeAllCartItems();

        foreach ($items as $item) {
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

    /** Update cart item quantity and total price.
     *
     * @param $cartItemId
     * @param $quantity
     */
    public function updateCartItemQuantity($cartItemId, $quantity)
    {
        $items =
            $this->getCart()
                ->getItems();
        $index = array_search($cartItemId, array_pluck($items, 'id'));

        $cartItem = $this->getCartItem($cartItemId);

        $cartItem->setQuantity($quantity);
        $cartItem->setTotalPrice($quantity * $cartItem->getPrice());
        $items[$index] = $cartItem;
        $this->removeAllCartItems();

        foreach ($items as $item) {
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

    /** Get a cart item from the session based on cart item id
     *
     * @param $id
     * @return mixed|null
     */
    public function getCartItem($id)
    {
        $items =
            $this->getCart()
                ->getItems();
        $index = array_search($id, array_pluck($items, 'id'));

        return $items[$index];
    }

    /** Set on the session the number of payments
     *
     * @param $numberOfPayments
     */
    public function setPaymentPlanNumberOfPayments($numberOfPayments)
    {
        if (empty($numberOfPayments) || $numberOfPayments == 1) {
            $this->session->put(self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY, 1);
        } else {
            $this->session->put(self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY, $numberOfPayments);
        }
    }

    /** Lock payment plan
     *
     * @param $numberOfPaymentsToForce
     */
    public function lockPaymentPlan($numberOfPaymentsToForce)
    {
        $this->session->put(self::PAYMENT_PLAN_LOCKED_SESSION_KEY, $numberOfPaymentsToForce);
    }

    /**
     * Unlock payment plan
     */
    public function unlockPaymentPlan()
    {
        $this->session->remove(self::PAYMENT_PLAN_LOCKED_SESSION_KEY);
    }

    /** Set promo code on the session
     *
     * @param string $promoCode
     */
    public function setPromoCode($promoCode)
    {
        $this->session->put(self::PROMO_CODE_KEY, $promoCode);
    }

    /** Get promo code from the session
     *
     * @return mixed
     */
    public function getPromoCode()
    {
        return $this->session->get(self::PROMO_CODE_KEY);
    }

    /** Add item to cart, calculate the shipping costs based on cart's products, apply discounts on cart items
     * @param $name
     * @param $description
     * @param $quantity
     * @param $price
     * @param $requiresShippingAddress
     * @param $requiresBillingAddress
     * @param null $subscriptionIntervalType
     * @param null $subscriptionIntervalCount
     * @param array $options
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
        $options = []
    ) {
        $product = $this->productRepository->read($options['product-id']);

        $shippingCosts = $this->shippingOptionsRepository->getShippingCosts(
                $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE)['country'],
                $this->cart->getTotalWeight() + $product->weight * $quantity
            )['price'] ?? 0;
        $this->cart->getCart()
            ->setShippingCosts($shippingCosts);

        // If the item already exists, just increase the quantity
        foreach ($this->cart->getItems() as $cartItem) {
            if (!empty($cartItem->getOptions()['product-id']) &&
                $cartItem->getOptions()['product-id'] == $options['product-id']) {
                $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
                $cartItem->setTotalPrice($cartItem->getTotalPrice() + $quantity * $cartItem->getPrice());

                $discountsToApply = $this->getDiscountsToApply();
                $this->discountService->applyDiscounts(
                    $discountsToApply,
                    $this->getCart()
                );
                $this->cart->getCart()
                    ->addDiscount($discountsToApply);
                return $this->applyDiscounts();
            }
        }

        $cartItem = new CartItem();
        $cartItem->setId(bin2hex(openssl_random_pseudo_bytes(32)));
        $cartItem->setName($name);
        $cartItem->setDescription($description);
        $cartItem->setQuantity($quantity);
        $cartItem->setPrice($price);
        $cartItem->setTotalPrice($quantity * $price);
        $cartItem->setRequiresShippingAddress($requiresShippingAddress);
        $cartItem->setRequiresBillingAddress($requiresBillingAddress);
        $cartItem->setSubscriptionIntervalType($subscriptionIntervalType);
        $cartItem->setSubscriptionIntervalCount($subscriptionIntervalCount);
        $cartItem->setProduct($product);
        $cartItem->setOptions($options);

        $this->cart->addCartItem($cartItem);

        $discountsToApply = $this->getDiscountsToApply();

        $this->discountService->applyDiscounts(
            $discountsToApply,
            $this->getCart()
        );

        $this->cart->getCart()
            ->addDiscount($discountsToApply);

        return $this->applyDiscounts();
    }

    /**
     * Get the cart entity from the session. If the cart it's not set on the session an empty cart it's returned.
     *
     * @return Cart
     */
    public function getCart()
    {
        return $this->cart->getCart();
    }

    /**
     * Return an array with the discounts that should be applied, the discounts criteria are met
     *
     * @return array
     */
    public function getDiscountsToApply()
    {
        $discountsToApply = [];
        $activeDiscounts =
            $this->discountRepository->query()
                ->where('active', 1)
                ->get();

        foreach ($activeDiscounts as $activeDiscount) {
            $criteriaMet = true;
            foreach ($activeDiscount->criteria as $discountCriteria) {
                if (!$this->discountCriteriaService->discountCriteriaMetForOrder(
                    $discountCriteria,
                    $this->cart->getCart(),
                    $this->getPromoCode()
                )) {
                    $criteriaMet = false;
                }
            }

            if ($criteriaMet) {
                $discountsToApply[$activeDiscount->id] = $activeDiscount;
            }
        }
        return $discountsToApply;
    }

    /** Calculate the discounted price on items and the discounted amount on cart(discounts that should be applied on order)
     *  and set the discounted price on order item and the total discount amount on cart.
     *
     * Return the cart with the discounts applied.
     *
     * @return Cart
     */
    public function applyDiscounts()
    {
        foreach (
            $this->cart->getCart()
                ->getDiscounts() as $discount
        ) {

            foreach (
                $this->cart->getCart()
                    ->getItems() as $index => $item
            ) {

                if (($discount['product_id'] == $item->getOptions()['product-id']) ||
                    (($discount['product_category']) &&
                        ($discount['product_category'] == $item->getProduct()['category']))) {
                    $productDiscount = 0;

                    if ($discount->type == DiscountService::PRODUCT_AMOUNT_OFF_TYPE) {
                        $productDiscount = $discount->amount * $item->getQuantity();
                    }

                    if ($discount->type == DiscountService::PRODUCT_PERCENT_OFF_TYPE) {
                        $productDiscount = $discount->amount / 100 * $item->getPrice() * $item->getQuantity();
                    }

                    if ($discount->type == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                        $productDiscount = $discount->amount * $item->getQuantity();
                    }
                    $this->cart->getCart()
                        ->getItems()[$index]->setDiscountedPrice(max(($item->getTotalPrice() - $productDiscount), 0));
                }
            }
        }

        $discountedAmount = $this->discountService->getAmountDiscounted(
            $this->cart->getCart()
                ->getDiscounts(),
            $this->cart->getCart()
                ->getTotalDue(),
            $this->cart->getCart()
                ->getItems()
        );

        $this->cart->getCart()
            ->setTotalDiscountAmount($discountedAmount);

        return $this->cart->getCart();
    }

}