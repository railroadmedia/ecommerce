<?php

namespace Railroad\Ecommerce\Entities;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\ShippingService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Location\Services\LocationService;

class Cart
{
    // services
    /**
     * @var ShippingService
     */
    private $shippingService;

    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * @var DiscountService
     */
    private $discountService;

    // cart attributes
    /**
     * @var CartItem[]
     */
    private $items = [];

    /**
     * @var Address
     */
    private $billingAddress;

    /**
     * @var Address
     */
    private $shippingAddress;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var integer
     */
    private $numberOfPayments = 1;

    /**
     * @var string
     */
    private $promoCode;

    /**
     * @var string
     */
    private $currency;

    const CART_SESSION_KEY = 'shopping_cart';

    /**
     * Cart constructor.
     *
     * @param ShippingService $shippingService
     * @param TaxService $taxService
     * @param DiscountService $discountService
     */
    public function __construct(
        ShippingService $shippingService,
        TaxService $taxService,
        DiscountService $discountService
    ) {
        $this->taxService = $taxService;
        $this->discountService = $discountService;
        $this->shippingService = $shippingService;
    }

    /**
     * @return float
     */
    public function getFirstPaymentAmount()
    {
        $orderTotal = $this->getOrderTotalDue();

        if ($this->getNumberOfPayments() > 1) {
            $paymentAmount = $this->getPerPaymentAmount($orderTotal);

            // we need to add back in any rounded off amount from the payment plan payments
            $initialPaymentAmount = $paymentAmount + ($orderTotal - ($paymentAmount * $this->getNumberOfPayments()));

            // ex: $10.00 3 payments = (10 / 3) = 33.33 -> 33.33 * 3 = 99.99 -> payments must be:
            // 33.34, 33.33, 33.33
        } else {
            $initialPaymentAmount = $orderTotal;
        }

        return $initialPaymentAmount;
    }

    /**
     * @param $orderTotal
     * @return float
     */
    public function getPerPaymentAmount($orderTotal)
    {
        // always round down to the nearest cent
        return floor($orderTotal / $this->getNumberOfPayments() * 100);
    }

    /**
     * @return float
     */
    public function getOrderTotalDue()
    {
        $totalBeforeOrderDiscounts = $this->getSubTotalAfterTaxes();

        return max(round(($totalBeforeOrderDiscounts - $this->getTotalDiscountedByOrderDiscounts()), 2), 0);
    }

    /**
     * @return mixed
     */
    public function getTotalDiscountedByOrderDiscounts()
    {
        $amountDiscounted = 0;

        foreach ($this->discountService->getApplicableDiscounts($this) as $discount) {
            if ($discount['type'] == DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE) {
                $amountDiscounted += $discount['amount'];
                break;
            } elseif ($discount['type'] == DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE) {
                $amountDiscounted += $discount['amount'] / 100 * $this->getSubTotalAfterTaxes();
                break;
            }
        }

        return max(round($amountDiscounted, 2), 0);
    }

    /**
     * @return float|int
     */
    public function getDiscountRatio()
    {
        return $this->getTotalDiscountedByOrderDiscounts() / $this->getSubTotalAfterTaxes();
    }

    /**
     * @return float
     */
    public function getSubTotalAfterTaxes()
    {
        $taxableSubTotal = $this->getTaxableSubTotal();

        $taxTotal = $this->getTaxTotal($taxableSubTotal);

        return $taxableSubTotal + $taxTotal;
    }

    /**
     * @return float
     */
    public function getTaxableSubTotal()
    {
        return $this->getItemSubTotalAfterDiscounts() + $this->getShippingTotalAfterDiscounts();
    }

    /**
     * @return float
     */
    public function getShippingTotalAfterDiscounts()
    {
        $shippingCost = $this->getShippingTotalBeforeDiscounts();

        $amountDiscounted = 0;

        foreach ($this->discountService->getApplicableDiscounts($this) as $discount) {
            if ($discount['type'] == DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE) {

                $amountDiscounted += $discount['amount'];
            } elseif ($discount['type'] == DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE) {

                $amountDiscounted += round($discount['amount'] / 100 * $shippingCost, 2);
            } elseif ($discount['type'] == DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {

                return $discount['amount'];
            }
        }

        return max(round(($shippingCost - $amountDiscounted), 2), 0);
    }

    /**
     * @return float
     */
    public function getShippingTotalBeforeDiscounts()
    {
        return $this->shippingService->getShippingCost(
            $this->getShippingAddress()['country'] ?? '',
            $this->getTotalWeight()
        );
    }

    /**
     * @param float $cost
     * @return float
     */
    public function getTaxTotal($cost)
    {
        return round(
            $cost * $this->taxService->getTaxRate(
                $this->getBillingAddress()['country'] ?? '',
                $this->getBillingAddress()['state'] ?? ''
            ),
            2
        );
    }

    /**
     * @return float
     */
    public function getItemSubTotal()
    {
        $subTotal = 0;

        foreach ($this->getItems() as $item) {
            $subTotal += $item->product['price'];
        }

        return $subTotal;
    }

    /**
     * @return float
     */
    public function getItemSubTotalAfterDiscounts()
    {
        $applicableDiscounts = $this->discountService->getApplicableDiscounts($this);

        $subTotal = 0;

        foreach ($this->getItems() as $item) {
            $subTotal += $item->getPriceAfterDiscounts($applicableDiscounts);
        }

        return $subTotal;
    }

    /**
     * @return CartItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return Discount[]
     */
    public function getApplicableDiscounts()
    {
        return $this->discountService->getApplicableDiscounts($this);
    }

    /**
     * @return int|mixed
     */
    public function getTotalWeight()
    {
        $weight = 0;

        foreach ($this->getItems() as $item) {
            $weight += $item->product['weight'];
        }

        return $weight;
    }

    /**
     * @return Address
     */
    public function getBillingAddress()
    {
        if (empty($this->billingAddress['country'])) {
            /**
             * @var $locationService LocationService
             */
            $locationService = app()->make(LocationService::class);

            $this->billingAddress = new Address(
                ['country' => $locationService->getCountry(), 'state' => $locationService->getRegion()]
            );

            $this->toSession();
        }

        return $this->billingAddress ?? new Address();
    }

    /**
     * @return Address
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress ?? new Address();
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * Get the number of payments for a payment plan.
     */
    public function getNumberOfPayments()
    {
        return $this->numberOfPayments;
    }

    /**
     * Get the promo code.
     */
    public function getPromoCode()
    {
        return $this->promoCode;
    }

    /**
     * @param $productId
     * @return null|CartItem
     */
    public function getItem($productId)
    {
        foreach ($this->getItems() as $item) {
            if ($item->product['id'] == $productId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Add item to cart. If the item already exists, just increase the quantity.
     *
     * @param Product $product
     * @param $quantity
     */
    public function addItem(Product $product, $quantity)
    {
        if ($this->isLocked()) {
            $this->unlock();
        }

        foreach ($this->getItems() as $itemIndex => $item) {
            if ($item->product['id'] == $product['id']) {
                $this->items[$itemIndex]->quantity += $quantity;

                return;
            }
        }

        $this->items[] = new CartItem($product, $quantity);

        return;
    }

    /**
     * Update cart item quantity.
     *
     * @param $productId
     * @param $quantity
     */
    public function updateItemQuantity($productId, $quantity)
    {
        foreach ($this->getItems() as $itemIndex => $item) {
            if ($item->product['id'] == $productId) {
                if ($quantity > 0) {
                    $this->items[$itemIndex]->quantity = $quantity;
                } else {
                    $this->removeItem($productId);
                }
            }
        }
    }

    /**
     * Delete cart item by product id.
     *
     * @param $productId
     */
    public function removeItem($productId)
    {
        foreach ($this->getItems() as $itemIndex => $item) {
            if ($item->product['id'] == $productId) {
                unset($this->items[$itemIndex]);
            }
        }
    }

    /**
     * @param Address $billingAddress
     */
    public function setBillingAddress(Address $billingAddress)
    {
        $this->billingAddress = $billingAddress;
    }

    /**
     * @param Address $shippingAddress
     */
    public function setShippingAddress(Address $shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * Lock the cart so if any other items are added it is cleared first.
     */
    public function lock()
    {
        $this->clear();

        $this->locked = true;
    }

    /**
     * Unlock the cart, clearing everything.
     */
    public function unlock()
    {
        $this->clear();

        $this->locked = false;
    }

    /**
     * @param integer $number
     */
    public function setNumberOfPayments($number)
    {
        $this->numberOfPayments = $number;
    }

    /**
     * @param string $promoCode
     */
    public function setPromoCode($promoCode)
    {
        $this->promoCode = $promoCode;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency ?? ConfigService::$defaultCurrency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Remove all cart items.
     */
    public function removeAllItems()
    {
        session()->forget(self::CART_SESSION_KEY);

        $this->items = [];
    }

    /**
     * Clear entire cart of all values.
     */
    public function clear()
    {
        $this->removeAllItems();
        $this->setNumberOfPayments(1);
        $this->setPromoCode(null);
        $this->setShippingAddress(new Address());
        $this->setBillingAddress(new Address());
        $this->locked = false;

        $this->toSession();
    }

    /**
     * Save current cart in the session.
     */
    public function toSession()
    {
        session()->put(
            self::CART_SESSION_KEY,
            serialize($this->toArray())
        );
    }

    /**
     * Populate cart from the session.
     */
    public function fromSession()
    {
        if (session()->has(self::CART_SESSION_KEY)) {
            $sessionData = unserialize(session()->get(self::CART_SESSION_KEY));

            $items = [];

            foreach ($sessionData['items'] as $cartItem) {
                $items[] = new CartItem(new Product($cartItem['product']), $cartItem['quantity']);
            }

            $this->items = $items;
            $this->locked = $sessionData['locked'] ?? false;
            $this->numberOfPayments = $sessionData['numberOfPayments'] ?? 1;
            $this->billingAddress = new Address($sessionData['billingAddress'] ?? []);
            $this->shippingAddress = new Address($sessionData['shippingAddress'] ?? []);
            $this->promoCode = $sessionData['promoCode'] ?? null;
            $this->currency = $sessionData['currency'] ?? null;
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $items = [];

        foreach ($this->items as $item) {
            $items[] = $item->toArray($this->discountService->getApplicableDiscounts($this));
        }

        return [
            'itemSubTotal' => $this->getItemSubTotal(),
            'itemSubTotalAfterDiscounts' => $this->getItemSubTotalAfterDiscounts(),
            'shippingBeforeDiscounts' => $this->getShippingTotalBeforeDiscounts(),
            'shippingAfterDiscounts' => $this->getShippingTotalAfterDiscounts(),
            'taxTotal' => $this->getTaxTotal($this->getTaxableSubTotal()),
            'orderTotal' => $this->getOrderTotalDue(),
            'firstPaymentTotal' => $this->getFirstPaymentAmount(),
            'PerPaymentAmount' => $this->getPerPaymentAmount($this->getOrderTotalDue()),

            'items' => $items,
            'locked' => $this->locked,
            'numberOfPayments' => $this->numberOfPayments,
            'billingAddress' => $this->getBillingAddress()
                ->getArrayCopy(),
            'shippingAddress' => $this->getShippingAddress()
                ->getArrayCopy(),
            'promoCode' => $this->promoCode,
            'currency' => $this->currency,
        ];
    }
}