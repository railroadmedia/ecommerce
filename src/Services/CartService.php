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
     * @var TaxService
     */
    private $taxService;

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
        ShippingOptionRepository $shippingOptionRepository,
        TaxService $taxService
    ) {
        $this->session = $session;
        $this->productRepository = $productRepository;
        $this->discountRepository = $discountRepository;
        $this->cartAddressService = $cartAddressService;
        $this->discountCriteriaService = $discountCriteriaService;
        $this->discountService = $discountService;
        $this->shippingOptionsRepository = $shippingOptionRepository;
        $this->taxService = $taxService;
        $this->cart = new Cart();
    }

    /** Add item to cart. If the item already exists, just increase the quantity.
     *
     * @param string $name
     * @param string $description
     * @param int $quantity
     * @param int $price
     * @param boolean $requiresShippingAddress
     * @param boolean $requiresBillingAddress
     * @param null $subscriptionIntervalType
     * @param null $subscriptionIntervalCount
     * @param array $options
     * @return array
     */
    public function addItemCard(
        $name,
        $description,
        $quantity,
        $price,
        $requiresShippingAddress,
        $requiresBillingAddress,
        $subscriptionIntervalType = null,
        $subscriptionIntervalCount = null,
        $weight,
        $options = []
    ) {
        $cartItems = $this->getAllCartItems();

        // if a product id is passed with any of the cart items, attach the entire product
        $productIds = [];
        $productsById = [];

        foreach ($cartItems as $cartItem) {
            if (!empty($cartItem['options']['product-id'])) {
                $productIds[] = $cartItem['options']['product-id'];
            }
        }

        if (!empty($options['product-id'])) {
            $options['product'] = $this->productRepository->read($options['product-id']);
        }

        foreach ($cartItems as $cartItemIndex => $cartItem) {
            if (!empty($cartItem['options']['product-id']) &&
                !empty($productsById[$cartItem['options']['product-id']])) {
                $cartItems[$cartItemIndex]['options']['product'] = $productsById[$cartItem['options']['product-id']];
            }
        }

        // If the item already exists, just increase the quantity
        foreach ($cartItems as $cartItem) {
            if (!empty($cartItem['options']['product-id']) &&
                $cartItem['options']['product-id'] == $options['product-id']) {
                $cartItem['quantity'] = ($cartItem['quantity'] + $quantity);
                $cartItem['totalPrice'] = $cartItem['quantity'] * $cartItem['price'];
                $cartItem['weight'] = $cartItem['quantity'] * $weight;

                $this->session->put(ConfigService::$brand . '-' . self::SESSION_KEY . $cartItem['id'], $cartItem);

                return $this->getAllCartItems();
            }
        }

        $cartItem = [
            'id' => (bin2hex(openssl_random_pseudo_bytes(32))),
            'name' => $name,
            'description' => $description,
            'quantity' => $quantity,
            'price' => $price,
            'totalPrice' => $quantity * $price,
            'requiresShippingAddress' => $requiresShippingAddress,
            'requiresBillinggAddress' => $requiresBillingAddress,
            'subscriptionIntervalType' => $subscriptionIntervalType,
            'subscriptionIntervalCount' => $subscriptionIntervalCount,
            'weight' => $quantity * $weight,
            'options' => $options,
        ];

        $this->session->put(ConfigService::$brand . '-' . self::SESSION_KEY . $cartItem['id'], $cartItem);

        return $this->getAllCartItems();
    }

    /** Return an array with the cart items.
     *
     * @return array
     */
    public function getAllCartItems()
    {
        $cartItems = [];
        return $this->cart->getItems();
        foreach ($this->session->all() as $sessionKey => $sessionValue) {
            if (substr($sessionKey, 0, strlen(ConfigService::$brand . '-' . self::SESSION_KEY)) ==
                ConfigService::$brand . '-' . self::SESSION_KEY) {
                $cartItem = $sessionValue;

                if (!empty($cartItem)) {
                    $cartItems[] = $cartItem;
                }
            }
        }

        return $cartItems;
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
        if ($this->session->has(ConfigService::$brand . '-' . self::SESSION_KEY . $id)) {
            $this->session->remove(ConfigService::$brand . '-' . self::SESSION_KEY . $id);
        }
    }

    /** Update cart item quantity and total price.
     *
     * @param $cartItemId
     * @param $quantity
     */
    public function updateCartItemQuantity($cartItemId, $quantity)
    {
        $cartItem = $this->getCartItem($cartItemId);
        $cartItem['quantity'] = $quantity;
        $cartItem['totalPrice'] = $quantity * $cartItem['price'];

        $this->session->put(ConfigService::$brand . '-' . self::SESSION_KEY . $cartItemId, $cartItem);
    }

    /** Get a cart item from the session based on cart item id
     *
     * @param $id
     * @return mixed|null
     */
    public function getCartItem($id)
    {
        if ($this->session->has(ConfigService::$brand . '-' . self::SESSION_KEY . $id)) {
            $cartItem = $this->session->get(ConfigService::$brand . '-' . self::SESSION_KEY . $id);

            if (!empty($cartItem)) {
                return $cartItem;
            }
        }
        return null;
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

    /** Get the number of payments
     *
     * @return mixed|integer
     */
    public function getPaymentPlanNumberOfPayments()
    {
        if ($this->session->has(self::PAYMENT_PLAN_LOCKED_SESSION_KEY) &&
            $this->session->get(self::PAYMENT_PLAN_LOCKED_SESSION_KEY) > 0) {
            return $this->session->get(self::PAYMENT_PLAN_LOCKED_SESSION_KEY, 1);
        }

        return $this->session->get(self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY, 1);
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

    public function addCartItem(
        $name,
        $description,
        $quantity,
        $price,
        $requiresShippingAddress,
        $requiresBillingAddress,
        $subscriptionIntervalType = null,
        $subscriptionIntervalCount = null,
        $weight,
        $options = []
    ) {
        $product = $this->productRepository->read($options['product-id']);

        $shippingCosts = $this->shippingOptionsRepository->getShippingCosts(
                $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE)['country'],
                $this->cart->getTotalWeight() + $product->weight * $quantity
            )['price'] ?? 0;
        $this->cart->setShippingCosts($shippingCosts);
        $this->cart->addDiscount($this->getDiscountsToApply());

        // If the item already exists, just increase the quantity
        foreach ($this->cart->getItems() as $cartItem) {
            if (!empty($cartItem->getOptions()['product-id']) &&
                $cartItem->getOptions()['product-id'] == $options['product-id']) {
                $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
                $cartItem->setTotalPrice($cartItem->getTotalPrice() + $quantity * $cartItem->getPrice());

                $this->session->put(self::SESSION_KEY, $this->cart);
                $this->taxService->calculateTaxesForCartItems();
                $this->applyDiscounts();
                return $this->cart->getItems();
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
        $this->applyDiscounts();
        $this->taxService->calculateTaxesForCartItems();



        return $this->cart->getItems();
    }

    public function calculateShippingDue()
    {
        return $this->cart->calculateShippingDue();
    }

    public function getCart()
    {
        return $this->cart->getCart();
    }

    public function getTotalDue()
    {
        return $this->cart->getTotalDue();
    }

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

    public function applyDiscounts()
    {
        foreach (
            $this->cart->getCart()
                ->getDiscounts() as $discount
        ) {
            foreach (
                $this->cart->getCart()
                    ->getItems() as $item
            ) {
                if (($discount['product_id'] == $item->getOptions()['product-id']) ||
                    ($discount['product_category'] == $item->getProduct()['category'])) {
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
                    $item->setDiscountedPrice(
                        (($item->getTotalPrice() - $productDiscount) > 0) ? $item->getTotalPrice() - $productDiscount :
                            0
                    );
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