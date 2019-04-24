<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderDiscount;
use Railroad\Ecommerce\Entities\OrderItem;
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

        $product = $this->productRepository->bySku($sku);

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
    public function updateCartQuantity(string $sku, int $quantity)
    {
        $this->refreshCart();

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

        $cartItem = $this->cart->getItemBySku($product->getSku());

        if (!empty($cartItem)) {
            $cartItem->setQuantity($quantity);

            $this->cart->setItem($cartItem);
        }

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
     * Returns the total cart items cost with discounts applied
     *
     * @return float
     */
    public function getTotalItemCosts()
    {
        $totalBeforeDiscounts = 0;

        $products = $this->productRepository->byCart($this->getCart());

        foreach ($products as $product) {
            $cartItem = $this->cart->getItemBySku($product->getSku());

            if (!empty($cartItem)) {
                $totalBeforeDiscounts += $product->getPrice() * $cartItem->getQuantity();
            }
        }

        $totalDiscountAmount = $this->discountService->getTotalItemDiscounted(
            $this->cart,
            $totalBeforeDiscounts,
            $this->shippingService->getShippingDueForCart($this->cart, $totalBeforeDiscounts)
        );

        return round($totalBeforeDiscounts - $totalDiscountAmount, 2);
    }

    /**
     * @return OrderItem[]
     * @throws Throwable
     * @throws \Doctrine\ORM\ORMException
     */
    public function getOrderItemEntities()
    {
        $orderItems = [];

        $products = $this->productRepository->byCart($this->getCart());

        $totalItemCosts = $this->getTotalItemCosts();
        $totalShippingCosts = $this->shippingService->getShippingDueForCart($this->getCart(), $totalItemCosts);

        foreach ($products as $product) {
            $cartItem = $this->cart->getItemBySku($product->getSku());

            if (!empty($cartItem)) {
                $orderItem = new OrderItem();

                $discountAmount = $this->discountService->getItemDiscountedAmount(
                    $this->getCart(),
                    $product->getSku(),
                    $totalItemCosts,
                    $totalShippingCosts
                );

                $orderItem->setProduct($product)
                    ->setQuantity($cartItem->getQuantity())
                    ->setWeight($product->getWeight())
                    ->setInitialPrice($product->getPrice())
                    ->setTotalDiscounted($discountAmount)
                    ->setFinalPrice(
                        round(
                            $product->getPrice() * $cartItem->getQuantity() - $discountAmount,
                            2
                        )
                    )
                    ->setCreatedAt(Carbon::now());

                $orderItemDiscounts = $this->discountService->getItemDiscounts(
                    $this->getCart(),
                    $product->getSku(),
                    $totalItemCosts,
                    $totalShippingCosts
                );

                foreach ($orderItemDiscounts as $discount) {
                    $orderItemDiscount = new OrderDiscount();

                    $orderItemDiscount->setDiscount($discount)
                        ->setOrderItem($orderItem);

                    $orderItem->addOrderItemDiscounts($orderItemDiscount);
                }

                $orderItems[] = $orderItem;
            }
        }

        return $orderItems;
    }

    /**
     * @return int
     */
    public function getTotalFinanceCosts()
    {
        if ($this->cart->getPaymentPlanNumberOfPayments() > 1) {
            return config('ecommerce.financing_cost_per_order', 1);
        }

        return 0;
    }

    /**
     * Returns the total due for the entire order. If a payment plan is selected their payment should add up to this
     * total when the monthly billing is finished.
     *
     * @return float
     */
    public function getDueForOrder()
    {
        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue = $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $taxDue = $this->taxService->vat(
            $totalItemCostDue + $shippingDue,
            $this->taxService->getAddressForTaxation($this->getCart())
        );

        $financeDue = $this->getTotalFinanceCosts();

        return round($totalItemCostDue + $shippingDue + $taxDue + $financeDue, 2);
    }

    /**
     * @return float
     */
    public function getTaxDueForOrder()
    {
        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue = $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $taxDue = $this->taxService->vat(
            $totalItemCostDue + $shippingDue,
            $this->taxService->getAddressForTaxation($this->getCart())
        );

        return round($taxDue, 2);
    }

    /**
     * Returns the initial payment amount that is so be paid immediately on order submit.
     *
     * @return float
     */
    public function getDueForInitialPayment()
    {
        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue = $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $taxDue = $this->taxService->vat(
            $totalItemCostDue + $shippingDue,
            $this->taxService->getAddressForTaxation($this->getCart())
        );

        $financeDue = $this->getTotalFinanceCosts();

        // Customers can only finance the order item price, taxes, and finance.
        // All shipping must be paid on the first payment.
        $totalToFinance = $totalItemCostDue + $taxDue + $financeDue;

        $initialTotalDueBeforeShipping = $totalToFinance / $this->cart->getPaymentPlanNumberOfPayments();

        // account for any rounded off cents by adding the difference after all payments to the first payment
        if ($initialTotalDueBeforeShipping * $this->cart->getPaymentPlanNumberOfPayments() != $totalToFinance) {
            $initialTotalDueBeforeShipping += abs(
                $initialTotalDueBeforeShipping * $this->cart->getPaymentPlanNumberOfPayments() - $totalToFinance
            );
        }

        return round($initialTotalDueBeforeShipping + $shippingDue, 2);
    }

    /**
     * Returns the price of the payment plan subscription that will be billed each month for the duration
     * of the payment plan.
     *
     * @return float
     */
    public function getDueForPaymentPlanPayments()
    {
        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue = $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $taxDue = $this->taxService->vat(
            $totalItemCostDue + $shippingDue,
            $this->taxService->getAddressForTaxation($this->getCart())
        );

        $financeDue = $this->getTotalFinanceCosts();

        $totalToFinance = $totalItemCostDue + $taxDue + $financeDue;

        return round(
            $totalToFinance / $this->cart->getPaymentPlanNumberOfPayments(),
            2
        );
    }

    /**
     * @param Order|null $order
     * @return Order|null
     */
    public function populateOrderTotals(?Order $order = null)
    {
        if (is_null($order)) {
            $order = new Order();
        }

        $totalItemCostDue = $this->getTotalItemCosts();

        $order->setFinanceDue($this->getTotalFinanceCosts());
        $order->setProductDue($totalItemCostDue);
        $order->setShippingDue($this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue));
        $order->setTaxesDue(
            $this->taxService->vat(
                $order->getProductDue() + $order->getShippingDue(),
                $this->taxService->getAddressForTaxation($this->getCart())
            )
        );

        return $order;
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
     * Sets the local cart property from session
     */
    public function refreshCart()
    {
        $this->cart = Cart::fromSession();
    }

    /**
     * Returns the current cart data structure
     *
     * @return array
     */
    public function toArray()
    {
        $this->refreshCart();

        $shippingAddress =
            !empty($this->cart->getShippingAddress()) ?
                $this->cart->getShippingAddress()
                    ->toArray() : null;
        $billingAddress =
            !empty($this->cart->getBillingAddress()) ?
                $this->cart->getBillingAddress()
                    ->toArray() : null;

        $numberOfPayments = $this->cart->getPaymentPlanNumberOfPayments() ?? 1;

        $due = ($numberOfPayments > 1) ? $this->getTotalDueForInitialPayment() : $this->getDueForOrder();

        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue = $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $taxDue = $this->taxService->vat(
            $totalItemCostDue + $shippingDue,
            $this->taxService->getAddressForTaxation($this->getCart())
        );

        $totals = [
            'shipping' => $shippingDue,
            'tax' => round($taxDue, 2),
            'due' => $due,
        ];

        $discounts = $this->discountService->getApplicableDiscountsNames($this->cart, $totalItemCostDue, $shippingDue) ?? [];

        $items = [];

        foreach ($this->cart->getItems() as $cartItem) {
            $product = $this->productRepository->bySku($cartItem->getSku());

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
                'price_after_discounts' => $product->getPrice() -
                    $this->discountService->getItemDiscountedAmount($this->cart, $cartItem->getSku(), $totalItemCostDue, $shippingDue),
            ];
        }

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
