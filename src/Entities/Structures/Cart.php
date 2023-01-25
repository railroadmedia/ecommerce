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
     * @var int|null
     */
    private $shippingAddressId;

    /**
     * @var int|null
     */
    private $billingAddressId;

    /**
     * @var int|null
     */
    private $paymentMethodId;

    /**
     * @var string|null
     */
    private $currency;

    /**
     * @var float|null
     */
    private $productTaxOverride;

    /**
     * @var float|null
     */
    private $shippingTaxOverride;

    /**
     * @var float|null
     */
    private $shippingOverride;

    /**
     * @var bool
     */
    private $enableMembershipChangeDiscounts = false;

    /**
     * @return bool
     */
    public function getMembershipChangeDiscountsEnabled()
    {
        return $this->enableMembershipChangeDiscounts;
    }

    /**
     * @param bool $enabled
     * @return void
     */
    public function setMembershipChangeDiscountsEnabled(bool $enabled)
    {
        $this->enableMembershipChangeDiscounts = $enabled;
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
     */
    public function setItem(CartItem $cartItem)
    {
        $this->items[$cartItem->getSku()] = $cartItem;
    }

    /**
     * @param CartItem[] $cartItems
     */
    public function replaceItems(array $cartItems)
    {
        $this->items = $cartItems;
    }

    /**
     * Add cart on session
     *
     * @param string $sku
     */
    public function removeItemBySku(string $sku)
    {
        unset($this->items[$sku]);
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
     * @return int|null
     */
    public function getShippingAddressId(): ?int
    {
        return $this->shippingAddressId;
    }

    /**
     * @param int|null $shippingAddressId
     */
    public function setShippingAddressId(?int $shippingAddressId): void
    {
        $this->shippingAddressId = $shippingAddressId;
    }

    /**
     * @return int|null
     */
    public function getBillingAddressId(): ?int
    {
        return $this->billingAddressId;
    }

    /**
     * @param int|null $billingAddressId
     */
    public function setBillingAddressId(?int $billingAddressId): void
    {
        $this->billingAddressId = $billingAddressId;
    }

    /**
     * @return int|null
     */
    public function getPaymentMethodId(): ?int
    {
        return $this->paymentMethodId;
    }

    /**
     * @param int|null $paymentMethodId
     */
    public function setPaymentMethodId(?int $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     */
    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return float|null
     */
    public function getShippingOverride(): ?float
    {
        return $this->shippingOverride;
    }

    /**
     * @param float|null $shippingOverride
     */
    public function setShippingOverride(?float $shippingOverride): void
    {
        $this->shippingOverride = $shippingOverride;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(
            [
                'items' => $this->getItems(),
                'locked' => $this->getLocked(),
                'promo-code' => $this->getPromoCode(),
                'payment-plan-number-of-payments' => $this->getPaymentPlanNumberOfPayments(),
                'shipping-address' => $this->getShippingAddress(),
                'billing-address' => $this->getBillingAddress(),
                'shipping-address-id' => $this->getShippingAddressId(),
                'billing-address-id' => $this->getBillingAddressId(),
                'payment-method-id' => $this->getPaymentMethodId(),
                'shipping-override' => $this->getShippingOverride(),
            ]
        );
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        $this->replaceItems($data['items']);
        $this->setLocked($data['locked']);
        $this->setPromoCode($data['promo-code']);
        $this->setPaymentPlanNumberOfPayments($data['payment-plan-number-of-payments']);

        $this->setShippingAddress($data['shipping-address']);
        $this->setBillingAddress($data['billing-address']);
        $this->setShippingAddressId($data['shipping-address-id']);
        $this->setBillingAddressId($data['billing-address-id']);

        $this->setPaymentMethodId($data['payment-method-id']);

        $this->setShippingOverride($data['shipping-override']);

        $this->setCurrency($data['currency']);
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