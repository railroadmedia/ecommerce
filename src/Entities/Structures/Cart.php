<?php

namespace Railroad\Ecommerce\Entities\Structures;

use Serializable;

class Cart implements Serializable
{
    const SESSION_KEY = 'railroad-ecommerce-shopping-cart-';

    /**
     * @var CartItem[]
     */
    private $items = [];

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var string
     */
    private $promoCode = '';

    /**
     * @var int
     */
    private $paymentPlanNumberOfPayments = 1;

    /**
     * @var Address|null
     */
    private $shippingAddress;

    /**
     * @var Address|null
     */
    private $billingAddress;

    /**
     * Initial cart items cost, not discounted
     *
     * @var float
     */
    private $itemsCost;

    /**
     * Initial cart items shipping cost, not discounted
     *
     * @var float
     */
    private $shippingCost;

    /**
     * @var float
     */
    private $orderDiscountAmount;

    /**
     * @var float
     */
    private $shippingDiscountAmount;

    /**
     * @var string[]
     */
    private $cartDiscountNames;

    public function __construct()
    {
        $this->itemsCost = 0;
        $this->shippingCost = 0;
        $this->orderDiscountAmount = 0;
        $this->shippingDiscountAmount = 0;
        $this->cartDiscountNames = [];
    }

    /**
     * Get cart items
     *
     * @return CartItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get cart items
     *
     * @param string $sku
     * @return CartItem|null
     */
    public function getItemBySku(string $sku): ?CartItem
    {
        return $this->items[$sku] ?? null;
    }

    /**
     * Add cart on session. Only one cart item can exist per SKU, so you can update any quantity by passing a new
     * CartItem with an existing SKU and the new quantity.
     *
     * @param CartItem $cartItem
     * @return Cart
     */
    public function setItem(CartItem $cartItem): Cart
    {
        $this->items[$cartItem->getSku()] = $cartItem;

        return $this;
    }

    /**
     * @param CartItem[] $cartItems
     * @return Cart
     */
    public function replaceItems(array $cartItems): Cart
    {
        $this->items = $cartItems;

        return $this;
    }

    /**
     * Add cart on session
     *
     * @param string $sku
     * @return Cart
     */
    public function removeItemBySku(string $sku): Cart
    {
        unset($this->items[$sku]);

        return $this;
    }

    /**
     * @return array
     */
    public function listSkus()
    {
        $skus = [];

        foreach ($this->items as $item) {
            $skus[] = $item->getSku();
        }

        return $skus;
    }

    /**
     * @return bool
     */
    public function getLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     */
    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * @return string
     */
    public function getPromoCode(): string
    {
        return $this->promoCode;
    }

    /**
     * @param string $promoCode
     */
    public function setPromoCode(string $promoCode): void
    {
        $this->promoCode = $promoCode;
    }

    /**
     * @return float
     */
    public function getItemsCost(): float
    {
        return $this->itemsCost;
    }

    /**
     * @param float $itemsCost
     */
    public function setItemsCost(float $itemsCost): void
    {
        $this->itemsCost = $itemsCost;
    }

    /**
     * @return float
     */
    public function getShippingCost(): float
    {
        return $this->shippingCost;
    }

    /**
     * @param float $shippingCost
     */
    public function setShippingCost(float $shippingCost): void
    {
        $this->shippingCost = $shippingCost;
    }

    /**
     * @return float
     */
    public function getOrderDiscountAmount(): float
    {
        return $this->orderDiscountAmount;
    }

    /**
     * @param float $orderDiscountAmount
     */
    public function setOrderDiscountAmount(float $orderDiscountAmount): void
    {
        $this->orderDiscountAmount = $orderDiscountAmount;
    }

    /**
     * @return float
     */
    public function getShippingDiscountAmount(): float
    {
        return $this->shippingDiscountAmount;
    }

    /**
     * @param float $shippingDiscountAmount
     */
    public function setShippingDiscountAmount(float $shippingDiscountAmount): void
    {
        $this->shippingDiscountAmount = $shippingDiscountAmount;
    }

    /**
     * @return array
     */
    public function getCartDiscountNames(): array
    {
        return $this->cartDiscountNames;
    }

    /**
     * @param array $discountName
     */
    public function setCartDiscountNames(array $discountName): void
    {
        $this->cartDiscountNames = $discountName;
    }

    /**
     * @return int
     */
    public function getPaymentPlanNumberOfPayments(): int
    {
        return $this->paymentPlanNumberOfPayments;
    }

    /**
     * @param int $paymentPlanNumberOfPayments
     */
    public function setPaymentPlanNumberOfPayments(int $paymentPlanNumberOfPayments): void
    {
        $this->paymentPlanNumberOfPayments = $paymentPlanNumberOfPayments;
    }

    /**
     * @return Address|null
     */
    public function getShippingAddress(): ?Address
    {
        return $this->shippingAddress;
    }

    /**
     * @param Address|null $shippingAddress
     */
    public function setShippingAddress(?Address $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * @return Address|null
     */
    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    /**
     * @param Address|null $billingAddress
     */
    public function setBillingAddress(?Address $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize([
            'items' => $this->getItems(),
            'locked' => $this->getLocked(),
            'promo-code' => $this->getPromoCode(),
            'payment-plan-number-of-payments' => $this->getPaymentPlanNumberOfPayments(),
            'shipping-address' => $this->getShippingAddress(),
            'billing-address' => $this->getBillingAddress(),
        ]);
    }

    /**
     * @param array $data
     */
    public function unserialize($data)
    {
        $this->replaceItems($data['items']);
        $this->setLocked($data['locked']);
        $this->setPromoCode($data['promo-code']);
        $this->setPaymentPlanNumberOfPayments($data['payment-plan-number-of-payments']);
        $this->setShippingAddress($data['shipping-address']);
        $this->setBillingAddress($data['billing-address']);
    }

    public function toSession(): void
    {
        session()->put(self::SESSION_KEY . config('ecommerce.brand'), $this);
    }

    /**
     * @return Cart
     */
    public static function fromSession()
    {
        $cart = session()->get(self::SESSION_KEY . config('ecommerce.brand'));

        if ($cart instanceof Cart) {
            return $cart;
        }

        return new Cart();
    }
}