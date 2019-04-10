<?php

namespace Railroad\Ecommerce\Services;

use Doctrine\ORM\ORMException;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Exceptions\Cart\ProductNotActiveException;
use Railroad\Ecommerce\Exceptions\Cart\ProductNotFoundException;
use Railroad\Ecommerce\Exceptions\Cart\ProductOutOfStockException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class CartService
{
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
     * @var TaxService
     */
    private $taxService;

    /**
     * @var Product[]
     */
    private $allProducts = [];

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var ShippingService
     */
    private $shippingService;

    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';
    const PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY = 'payment-plan-number-of-payments';
    const PAYMENT_PLAN_LOCKED_SESSION_KEY = 'order-form-payment-plan-locked';
    const PROMO_CODE_KEY = 'promo-code';
    const TAXABLE_COUNTRY = 'Canada';

    /**
     * CartService constructor.
     *
     * @param DiscountService $discountService
     * @param EcommerceEntityManager $entityManager
     * @param PermissionService $permissionService
     * @param ProductRepository $productRepository
     * @param TaxService $taxService
     * @param ShippingService $shippingService
     */
    public function __construct(
        DiscountService $discountService,
        EcommerceEntityManager $entityManager,
        PermissionService $permissionService,
        ProductRepository $productRepository,
        TaxService $taxService,
        ShippingService $shippingService
    )
    {
        $this->discountService = $discountService;
        $this->entityManager = $entityManager;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
        $this->taxService = $taxService;
        $this->shippingService = $shippingService;
    }

    /**
     * Add products to cart; if the products are active and available(the
     * product stock > requested quantity). The success field from response
     * it's set to false if at least one product it's not active or available.
     *
     * @param          $sku
     * @param          $quantity
     * @param bool $lock
     * @param string $promoCode
     *
     * @return Product
     * @throws ProductNotActiveException
     * @throws ProductOutOfStockException
     * @throws Throwable
     *
     * @throws ProductNotFoundException
     */
    public function addToCart(
        string $sku,
        int $quantity,
        bool $lock = false,
        string $promoCode = ''
    ): Product
    {
        $this->refreshCart();

        // cart locking
        if ($lock) {
            $this->cart->setLocked(true);

        }
        elseif ($this->cart->getLocked()) {

            // if the cart is locked and a new item is added, we should wipe it first
            $this->cart = new Cart();
            $this->cart->toSession();
        }

        // promo code
        if (!empty($promoCode)) {
            $this->cart->setPromoCode($promoCode);
        }

        // product
        $product = $this->productRepository->bySku($sku);

        if (empty($product)) {
            throw new ProductNotFoundException($sku);
        }

        if (!$product->getActive()) {
            throw new ProductNotActiveException($product);
        }

        if ($product->getStock() !== null && $product->getStock() < $quantity) {
            throw new ProductOutOfStockException($product);
        }

        $this->cart->setItem(new CartItem($sku, $quantity));

        $this->cart->toSession();

        return $product;
    }

    /**
     * Removes the cart item
     *
     * @param string $sku
     *
     * @throws ProductNotFoundException
     * @throws Throwable
     */
    public function removeFromCart(string $sku)
    {
        $this->refreshCart();

        $product = $this->allProducts[$sku] ?? null;

        if (empty($product)) {
            throw new ProductNotFoundException($sku);
        }

        $this->cart->removeItemBySku($sku);

        $this->cart->toSession();
    }

    /**
     * Updates the cart item product quantity
     * If the operation is successful, null will be returned
     * A string error message will be returned on product active/stock errors
     *
     * @param string $sku
     * @param int $quantity
     *
     * @throws ProductNotFoundException
     * @throws ProductNotActiveException
     * @throws ProductOutOfStockException
     * @throws Throwable
     */
    public function updateCartItemProductQuantity(
        string $sku,
        int $quantity
    )
    {
        $this->refreshCart();

        // product
        $product = $this->allProducts[$sku] ?? null;

        if (empty($product)) {
            throw new ProductNotFoundException($sku);
        }

        if (!$product->getActive()) {
            throw new ProductNotActiveException($product);
        }

        if ($product->getStock() !== null && $product->getStock() < $quantity) {
            throw new ProductOutOfStockException($product);
        }

        $cartItem = $this->cart->getItemBySku($product->getSku());

        $cartItem->setQuantity($quantity);

        $this->cart->setItem($cartItem);

        $this->cart->toSession();
    }

    /**
     * Removes all cart items - initializes an empty cart - and stores it on session
     */
    public function clearCart()
    {
        $this->cart = new Cart();
        $this->cart->toSession();
    }

    /**
     * Sets the shipping address on cart
     * proxy method to trigger cart updates when shipping address is set or changes
     */
    public function setShippingAddress(Address $shippingAddress)
    {
        $this->refreshCart();

        $this->cart->setShippingAddress($shippingAddress);

        $this->cart->toSession();
    }

    /**
     * Sets the billing address on cart
     * proxy method to trigger cart updates when billing address is set or changes
     */
    public function setBillingAddress(Address $billingAddress)
    {
        $this->refreshCart();

        $this->cart->setBillingAddress($billingAddress);

        $this->cart->toSession();
    }

    /**
     * Sets the local cart property from session
     */
    public function refreshCart()
    {
        $this->cart = Cart::fromSession();
    }

    /**
     * @return bool
     * @throws ORMException
     */
    public function cartHasAnyDigitalItems()
    {
        $products = $this->productRepository->bySkus($this->cart->listSkus());

        foreach ($products as $product) {
            if (!$product->getIsPhysical()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the total cart items cost with discounts applied
     *
     * @return float
     */
    public function getTotalItemCostDue()
    {
        $productsDiscountAmount = 0;

        foreach ($this->cart->getItems() as $cartItem) {
            $productsDiscountAmount += $cartItem->getDiscountAmount();
        }

        return round($this->cart->getItemsCost() - $productsDiscountAmount - $this->cart->getOrderDiscountAmount(), 2);
    }

    /**
     * Should always use the shipping address for cart first, then the billing address if no shipping address is set.
     *
     * @return float
     * @throws ORMException
     */
    public function getTotalTaxDue()
    {
        $taxableAddress = null;
        $billingAddress = $this->cart->getBillingAddress();
        $shippingAddress = $this->cart->getShippingAddress();

        // use the shipping address if set
        if ($shippingAddress && strtolower($shippingAddress->getCountry()) == strtolower(self::TAXABLE_COUNTRY)) {
            $taxableAddress = $shippingAddress;
        }

        // otherwise use the billing address
        if (!$taxableAddress &&
            $billingAddress &&
            strtolower($billingAddress->getCountry()) == strtolower(self::TAXABLE_COUNTRY)) {
            $taxableAddress = $billingAddress;
        }

        // only item and shipping costs are taxed
        $amountToTax = $this->getTotalItemCostDue() + $this->shippingService->getShippingDueForCart($this->cart);

        $tax = 0;

        if ($taxableAddress) {
            $tax = $this->taxService->vat($amountToTax, $taxableAddress);
        }

        return round($tax, 2);
    }

    /**
     * Returns the total cart cost, including discounts, shipping, tax and finance
     *
     * @return float
     */
    public function getTotalDue()
    {
        $totalItemCostDue = $this->getTotalItemCostDue();

        $shippingDue = $this->shippingService->getShippingDueForCart($this->cart);

        $taxDue = $this->getTotalTaxDue();

        if ($this->cart->getPaymentPlanNumberOfPayments() > 1) {
            $financeDue = config('ecommerce.financing_cost_per_order');
        }
        else {
            $financeDue = 0;
        }

        return round($totalItemCostDue + $shippingDue + $taxDue + $financeDue, 2);
    }

    /**
     * Returns the recurring payment cost
     *
     * @return float
     */
    public function getDueForPayment()
    {
        $totalItemCostDue = $this->getTotalItemCostDue();

        $taxDue = $this->getTotalTaxDue();

        if ($this->cart->getPaymentPlanNumberOfPayments() > 1) {
            $financeDue = config('ecommerce.financing_cost_per_order');
        }
        else {
            $financeDue = 0;
        }

        // Customers can only finance the order item price, taxes, and finance.
        // All shipping must be paid on the first payment.
        $totalToFinance = $totalItemCostDue + $taxDue + $financeDue;

        return round(
            $totalToFinance / $this->cart->getPaymentPlanNumberOfPayments(),
            2
        );
    }

    /**
     * Returns the initial payment cost
     *
     * @return float
     */
    public function getDueForInitialPayment()
    {
        $shippingDue = $this->getTotalShippingDue();

        $totalItemCostDue = $this->getTotalItemCostDue();

        $taxDue = $this->getTotalTaxDue();

        if ($this->cart->getPaymentPlanNumberOfPayments() > 1) {
            $financeDue = config('ecommerce.financing_cost_per_order');
        }
        else {
            $financeDue = 0;
        }

        // Customers can only finance the order item price, taxes, and finance.
        // All shipping must be paid on the first payment.
        $totalToFinance = $totalItemCostDue + $taxDue + $financeDue;

        $initialTotalDueBeforeShipping = round(
            $totalToFinance / $this->cart->getPaymentPlanNumberOfPayments(),
            2
        );

        // account for any rounded off cents by adding the difference after all payments to the first payment
        if ($initialTotalDueBeforeShipping * $this->cart->getPaymentPlanNumberOfPayments() != $totalToFinance) {
            $initialTotalDueBeforeShipping += abs(
                $initialTotalDueBeforeShipping * $this->cart->getPaymentPlanNumberOfPayments() - $totalToFinance
            );
        }

        return round($initialTotalDueBeforeShipping + $shippingDue, 2);
    }

    /**
     * @param Cart $cart
     */
    public function setCart(Cart $cart): void
    {
        $this->cart = $cart;

        $this->cart->toSession();
    }

    /**
     * @return Cart
     */
    public function getCart(): Cart
    {
        return $this->cart;
    }

    /**
     * Returns the current cart data structure
     *
     * @return array
     */
    public function toArray()
    {
        $this->refreshCart();

        $items = [];

        foreach ($this->cart->getItems() as $cartItem) {
            $product = $this->allProducts[$cartItem->getSku()];

            if (empty($product)) {
                continue;
            }

            $items[] = [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'quantity' => $cartItem->getQuantity(),
                'thumbnail_url' => $product->getThumbnailUrl(),
                'description' => $product->getDescription(),
                'stock' => $product->getStock(),
                'subscription_interval_type' => $product->getSubscriptionIntervalType(),
                'subscription_interval_count' => $product->getSubscriptionIntervalCount(),
                'price_before_discounts' => $product->getPrice(),
                'price_after_discounts' => $product->getPrice() - $cartItem->getDiscountAmount(),
            ];
        }

        $shippingAddress =
            !empty($this->cart->getShippingAddress()) ?
                $this->cart->getShippingAddress()
                    ->toArray() : null;
        $billingAddress =
            !empty($this->cart->getBillingAddress()) ?
                $this->cart->getBillingAddress()
                    ->toArray() : null;

        $discounts = $this->cart->getCartDiscountNames() ?? [];

        $numberOfPayments = $this->cart->getPaymentPlanNumberOfPayments() ?? 1;

        $due = ($numberOfPayments > 1) ? $this->getDueForInitialPayment() : $this->getTotalDue();

        $totals = [
            'shipping' => $this->getTotalShippingDue(),
            'tax' => $this->getTotalTaxDue(),
            'due' => $due,
        ];

        return [
            'items' => $items,
            'discounts' => $discounts,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'number_of_payments' => $numberOfPayments,
            'totals' => $totals,
        ];
    }
}
