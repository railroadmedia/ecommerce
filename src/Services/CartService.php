<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\ORMException;
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
use Railroad\Ecommerce\Exceptions\Cart\UpdateNumberOfPaymentsCartException;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Location\Services\LocationService;
use Throwable;

class CartService
{
    /**
     * @var DiscountService
     */
    private $discountService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

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

    /**
     * @var LocationService
     */
    private $locationService;

    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';
    const PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY = 'payment-plan-number-of-payments';
    const PAYMENT_PLAN_LOCKED_SESSION_KEY = 'order-form-payment-plan-locked';
    const PROMO_CODE_KEY = 'promo-code';

    /**
     * CartService constructor.
     *
     * @param DiscountService $discountService
     * @param ProductRepository $productRepository
     * @param TaxService $taxService
     * @param ShippingService $shippingService
     * @param LocationService $locationService
     */
    public function __construct(
        DiscountService $discountService,
        ProductRepository $productRepository,
        TaxService $taxService,
        ShippingService $shippingService,
        LocationService $locationService
    )
    {
        $this->discountService = $discountService;
        $this->productRepository = $productRepository;
        $this->taxService = $taxService;
        $this->shippingService = $shippingService;
        $this->locationService = $locationService;
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
        } elseif ($this->cart->getLocked()) {

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

        if ($quantity < 1) {
            throw new ProductNotFoundException($sku);
        }

        if (empty($product)) {
            throw new ProductNotFoundException($sku);
        }

        if (!$product->getActive()) {
            throw new ProductNotActiveException($product);
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

        if ($quantity < 1) {
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
     * Update number of payments
     *
     * @param int $numberOfPayments
     *
     * @throws UpdateNumberOfPaymentsCartException
     * @throws Throwable
     */
    public function updateNumberOfPayments(int $numberOfPayments)
    {
        $this->refreshCart();

        $due = $this->getDueForOrder();

        if ($due < config('ecommerce.payment_plan_minimum_price') ||
            !in_array($numberOfPayments, config('ecommerce.payment_plan_options'))) {

            throw new UpdateNumberOfPaymentsCartException($numberOfPayments);
        }

        $this->cart->setPaymentPlanNumberOfPayments($numberOfPayments);

        $this->cart->toSession();
    }

    /**
     * @return bool
     *
     * @throws ORMException
     */
    public function hasAnyRecurringSubscriptionProducts()
    {
        $products = $this->productRepository->byCart($this->getCart());

        foreach ($products as $product) {
            if (in_array($product->getType(), [Product::TYPE_DIGITAL_SUBSCRIPTION])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the total cart items cost with discounts applied
     *
     * @return float
     *
     * @throws Throwable
     */
    public function getTotalItemCosts()
    {
        $totalBeforeDiscounts = 0;

        $products = $this->productRepository->byCart($this->getCart());

        foreach ($products as $product) {
            $cartItem = $this->cart->getItemBySku($product->getSku());

            if (!empty($cartItem)) {
                $price = !is_null($cartItem->getDueOverride()) ? $cartItem->getDueOverride() : $product->getPrice();
                $totalBeforeDiscounts += $price * $cartItem->getQuantity();
            }
        }

        $totalDiscountAmount = $this->discountService->getTotalItemDiscounted(
            $this->cart,
            $totalBeforeDiscounts,
            $this->shippingService->getShippingDueForCart($this->cart, $totalBeforeDiscounts)
        );

        return max(0, round($totalBeforeDiscounts - $totalDiscountAmount, 2));
    }

    /**
     * @return OrderItem[]
     * @throws Throwable
     * @throws ORMException
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
                    $product,
                    $totalItemCosts,
                    $totalShippingCosts
                );

                $cartItemDue = !is_null($cartItem->getDueOverride()) ? $cartItem->getDueOverride() : max(
                    round(
                        $product->getPrice() * $cartItem->getQuantity() - $discountAmount,
                        2
                    ),
                    0
                );

                $orderItem->setProduct($product);
                $orderItem->setQuantity($cartItem->getQuantity());
                $orderItem->setWeight($product->getWeight());
                $orderItem->setInitialPrice($product->getPrice());
                $orderItem->setTotalDiscounted($discountAmount);
                $orderItem->setFinalPrice($cartItemDue);
                $orderItem->setCreatedAt(Carbon::now());

                $orderItemDiscounts = $this->discountService->getItemDiscounts(
                    $this->getCart(),
                    $product->getSku(),
                    $totalItemCosts,
                    $totalShippingCosts
                );

                foreach ($orderItemDiscounts as $discount) {
                    $orderItemDiscount = new OrderDiscount();

                    $orderItemDiscount->setDiscount($discount);
                    $orderItemDiscount->setOrderItem($orderItem);

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
     *
     * @throws Throwable
     */
    public function getDueForOrder()
    {
        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue =
            !is_null($this->cart->getShippingOverride()) ? $this->cart->getShippingOverride() :
                $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $taxableAddress = $this->taxService->getAddressForTaxation($this->getCart());

        $productTaxDue = $this->taxService->getTaxesDueForProductCost(
            $totalItemCostDue,
            $taxableAddress
        );

        $shippingTaxDue = $this->taxService->getTaxesDueForShippingCost(
            $shippingDue,
            $taxableAddress
        );

        $totalTaxDue = $productTaxDue + $shippingTaxDue;

        $financeDue = $this->getTotalFinanceCosts();

        return max(0, round($totalItemCostDue + $shippingDue + $totalTaxDue + $financeDue, 2));
    }

    /**
     * @return float
     *
     * @throws Throwable
     */
    public function getTaxDueForOrder()
    {
        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue =
            !is_null($this->cart->getShippingOverride()) ? $this->cart->getShippingOverride() :
                $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $taxableAddress = $this->taxService->getAddressForTaxation($this->getCart());

        $productTaxDue = $this->taxService->getTaxesDueForProductCost(
            $totalItemCostDue,
            $taxableAddress
        );

        $shippingTaxDue = $this->taxService->getTaxesDueForShippingCost(
            $shippingDue,
            $taxableAddress
        );

        $totalTaxDue = round($productTaxDue + $shippingTaxDue, 2);

        return max(0, $totalTaxDue);
    }

    /**
     * @param integer $numberOfPayments
     * @return float
     *
     * @throws ORMException
     * @throws Throwable
     */
    public function getPaymentPlanRecurringPrice($numberOfPayments)
    {
        $totalItemCostDue = $this->getTotalItemCosts();

        $financeCosts = $this->getTotalFinanceCosts();

        return round(
            ($totalItemCostDue + $financeCosts) / $numberOfPayments,
            2
        );
    }

    /**
     * Returns the initial payment amount that is so be paid immediately on order submit.
     *
     * @return float
     *
     * @throws Throwable
     */
    public function getDueForInitialPayment()
    {
        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue =
            !is_null($this->cart->getShippingOverride()) ? $this->cart->getShippingOverride() :
                $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $taxableAddress = $this->taxService->getAddressForTaxation($this->getCart());

        $productTaxDue = $this->taxService->getTaxesDueForProductCost(
            $totalItemCostDue,
            $taxableAddress
        );

        $shippingTaxDue = $this->taxService->getTaxesDueForShippingCost(
            $shippingDue,
            $taxableAddress
        );

        $totalTaxDue = $productTaxDue + $shippingTaxDue;

        $financeDue = $this->getTotalFinanceCosts();

        // Customers can only finance the order item price, product taxes, and finance.
        // All shipping costs and shipping taxes must be paid on the first payment.
        $totalToFinance = $totalItemCostDue + $productTaxDue + $financeDue;

        $initialTotalDueBeforeShipping = round($totalToFinance / $this->cart->getPaymentPlanNumberOfPayments(), 2);

        // account for any rounded off cents by adding the difference after all payments to the first payment
        if ($initialTotalDueBeforeShipping * $this->cart->getPaymentPlanNumberOfPayments() != $totalToFinance) {
            $initialTotalDueBeforeShipping += $totalToFinance - ($initialTotalDueBeforeShipping *
                    $this->cart->getPaymentPlanNumberOfPayments());
        }

        return max(0, round($initialTotalDueBeforeShipping + $shippingDue + $shippingTaxDue, 2));
    }

    /**
     * Returns the price of the payment plan subscription that will be billed each month for the duration
     * of the payment plan.
     *
     * @return float
     *
     * @throws Throwable
     */
    public function getDueForPaymentPlanPayments()
    {
        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue =
            !is_null($this->cart->getShippingOverride()) ? $this->cart->getShippingOverride() :
                $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $taxableAddress = $this->taxService->getAddressForTaxation($this->getCart());

        $productTaxDue = $this->taxService->getTaxesDueForProductCost(
            $totalItemCostDue,
            $taxableAddress
        );


        $shippingTaxDue = $this->taxService->getTaxesDueForShippingCost(
            $shippingDue,
            $taxableAddress
        );

        $financeDue = $this->getTotalFinanceCosts();

        $totalToFinance = $totalItemCostDue + $productTaxDue + $financeDue;

        return max(
            0,
            round(
                $totalToFinance / $this->cart->getPaymentPlanNumberOfPayments(),
                2
            )
        );
    }

    /**
     * @param Order|null $order
     *
     * @return Order|null
     *
     * @throws Throwable
     */
    public function populateOrderTotals(?Order $order = null)
    {
        if (is_null($order)) {
            $order = new Order();
        }

        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDue =
            !is_null($this->cart->getShippingOverride()) ? $this->cart->getShippingOverride() :
                $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $order->setProductDue($totalItemCostDue);
        $order->setShippingDue($shippingDue);

        $taxableAddress = $this->taxService->getAddressForTaxation($this->getCart());

        $productTaxDue = $this->taxService->getTaxesDueForProductCost(
            $order->getProductDue(),
            $taxableAddress
        );

        $shippingTaxDue = $this->taxService->getTaxesDueForShippingCost(
            $order->getShippingDue(),
            $taxableAddress
        );

        $totalTaxDue = round($productTaxDue + $shippingTaxDue, 2);

        $order->setFinanceDue($this->getTotalFinanceCosts());

        $order->setTaxesDue($totalTaxDue);

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
     *
     * @throws Throwable
     */
    public function toArray()
    {
        $this->refreshCart();

        $shippingAddress =
            !empty($this->cart->getShippingAddress()) ?
                $this->cart->getShippingAddress()
                    ->toArray() : null;

        if (!$this->shippingService->doesCartHaveAnyPhysicalItems($this->cart)) {
            $shippingAddress = null;
        }

        $billingAddress =
            !empty($this->cart->getBillingAddress()) ?
                $this->cart->getBillingAddress()
                    ->toArray() : null;

        if ((empty($billingAddress) || empty($billingAddress['country'])) &&
            !empty($this->locationService->getCountry())) {
            $address = new Address($this->locationService->getCountry(), $this->locationService->getRegion());

            $this->cart->setBillingAddress($address);

            $this->cart->toSession();
            $billingAddress = $address->toArray();
        }

        $numberOfPayments = $this->cart->getPaymentPlanNumberOfPayments() ?? 1;

        $orderDue = $this->getDueForOrder();

        $due = ($numberOfPayments > 1) ? $this->getDueForInitialPayment() : $orderDue;

        $totalItemCostDue = $this->getTotalItemCosts();

        $shippingDueBeforeOverride = $this->shippingService->getShippingDueForCart($this->cart, $totalItemCostDue);

        $shippingDue =
            !is_null($this->cart->getShippingOverride()) ? $this->cart->getShippingOverride() :
                $shippingDueBeforeOverride;

        $taxableAddress = $this->taxService->getAddressForTaxation($this->getCart());

        $productTaxDue = $this->taxService->getTaxesDueForProductCost(
            $totalItemCostDue,
            $taxableAddress
        );

        $shippingTaxDue = $this->taxService->getTaxesDueForShippingCost(
            $shippingDue,
            $taxableAddress
        );

        $totalTaxDue = round($productTaxDue + $shippingTaxDue, 2);

        $totals = [
            'shipping' => $shippingDue,
            'shipping_before_override' => $shippingDueBeforeOverride,
            'tax' => $totalTaxDue,
            'due' => $due,
            'product_taxes' => round($productTaxDue, 2),
            'shipping_taxes' => round($shippingTaxDue, 2),
        ];

        $discounts =
            $this->discountService->getApplicableDiscountsNames($this->cart, $totalItemCostDue, $shippingDue) ?? [];

        $items = [];
        $productsBySku = $this->productRepository->bySkus($this->cart->listSkus());
        $productsBySku = key_array_of_entities_by($productsBySku, 'getSku');

        foreach ($this->cart->getItems() as $cartItem) {
            $product = $productsBySku[$cartItem->getSku()];

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
                'subscription_interval_type' => $product->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                    $product->getSubscriptionIntervalType() : null,
                'subscription_interval_count' => $product->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                    $product->getSubscriptionIntervalCount() : null,
                'price_before_discounts' => $product->getPrice() * $cartItem->getQuantity(),
                'price_after_discounts' => max(
                    round(
                        ($product->getPrice() * $cartItem->getQuantity()) -
                        $this->discountService->getItemDiscountedAmount(
                            $this->cart,
                            $product,
                            $totalItemCostDue,
                            $shippingDue
                        ),
                        2
                    ),
                    0
                ),
                'requires_shipping' => $product->getIsPhysical(),
                'is_digital' => ($product->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                    $product->getType() == Product::TYPE_DIGITAL_ONE_TIME),
            ];
        }

        $paymentPlanOptions = [];
        $financeCost = config('ecommerce.financing_cost_per_order', 1);

        if ($orderDue > config('ecommerce.payment_plan_minimum_price')) {

            foreach (config('ecommerce.payment_plan_options') as $paymentPlanOption) {
                $orderDueForPlan = $orderDue;
                $label = null;

                if ($numberOfPayments > 1 && $paymentPlanOption == 1) {
                    $orderDueForPlan = $orderDue - $financeCost;
                }
                if ($numberOfPayments == 1 && $paymentPlanOption > 1) {
                    $orderDueForPlan = $orderDue + $financeCost;
                }

                if ($paymentPlanOption == 1) {
                    $label = '1 payment of $' . $orderDueForPlan;
                } else {
                    $format = '%s payments of $%s ($%s finance charge)';
                    $label = sprintf(
                        $format,
                        $paymentPlanOption,
                        round(($orderDueForPlan - $shippingDue) / $paymentPlanOption, 2),
                        number_format($financeCost, 2)
                    );
                }

                $paymentPlanOptions[] = [
                    'value' => $paymentPlanOption,
                    'label' => $label,
                ];
            }
        }

        return [
            'items' => $items,
            'discounts' => $discounts,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'number_of_payments' => $numberOfPayments,
            'payment_plan_options' => $paymentPlanOptions,
            'locked' => $this->cart->getLocked(),
            'totals' => $totals,
        ];
    }
}
