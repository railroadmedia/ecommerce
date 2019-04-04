<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Exceptions\Cart\ProductNotActiveException;
use Railroad\Ecommerce\Exceptions\Cart\ProductNotFoundException;
use Railroad\Ecommerce\Exceptions\Cart\ProductOutOfStockException;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Permissions\Services\PermissionService;

class CartService
{
    /**
     * @var DiscountCriteriaService
     */
    private $discountCriteriaService;

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

    /**
     * @var Product[]
     */
    private $allProducts = [];

    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';
    const PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY = 'payment-plan-number-of-payments';
    const PAYMENT_PLAN_LOCKED_SESSION_KEY = 'order-form-payment-plan-locked';
    const PROMO_CODE_KEY = 'promo-code';
    const TAXABLE_COUNTRY = 'Canada';

    /**
     * @var Cart
     */
    private $cart;

    /**
     * CartService constructor.
     *
     * @param  DiscountCriteriaService  $discountCriteriaService
     * @param  DiscountService  $discountService
     * @param  EcommerceEntityManager  $entityManager
     * @param  PermissionService  $permissionService
     * @param  ProductRepository  $productRepository
     * @param  ShippingOptionRepository  $shippingOptionRepository
     * @param  Store  $session
     * @param  TaxService  $taxService
     */
    public function __construct(
        DiscountCriteriaService $discountCriteriaService,
        DiscountService $discountService,
        EcommerceEntityManager $entityManager,
        PermissionService $permissionService,
        ProductRepository $productRepository,
        ShippingOptionRepository $shippingOptionRepository,
        Store $session,
        TaxService $taxService
    ) {
        $this->discountCriteriaService = $discountCriteriaService;
        $this->discountService = $discountService;
        $this->entityManager = $entityManager;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
        $this->shippingOptionRepository = $shippingOptionRepository;
        $this->session = $session;
        $this->taxService = $taxService;

        // lets cache all the products right from the start
        $allProducts = $this->productRepository->findAll();

        foreach ($allProducts as $product) {
            $this->allProducts[$product->getSku()] = $product;
        }
    }

    /**
     * Add products to cart; if the products are active and available(the
     * product stock > requested quantity). The success field from response
     * it's set to false if at least one product it's not active or available.
     *
     * @param          $sku
     * @param          $quantity
     * @param  bool  $lock
     * @param  string  $promoCode
     *
     * @throws ProductNotFoundException
     * @throws ProductNotActiveException
     * @throws ProductOutOfStockException
     *
     * @return Product
     */
    public function addToCart(
        string $sku,
        int $quantity,
        bool $lock = false,
        string $promoCode = ''
    ): Product {

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
        $product = $this->allProducts[$sku] ?? null;

        if (empty($product)) {
            throw new ProductNotFoundException($sku);
        }

        if (!$product->getActive()) {
            throw new ProductNotActiveException($product);
        }

        if ($product->getStock() !== null
            && $product->getStock() < $quantity
        ) {
            throw new ProductOutOfStockException($product);
        }

        $this->cart->setItem(new CartItem($sku, $quantity));

        $this->cart->toSession();

        $this->discountService->applyDiscountForCart();

        return $product;
    }

    /**
     * Removes the cart item
     *
     * @param  string  $sku
     *
     * @return bool
     */
    public function removeFromCart(string $sku)
    {
        $this->refreshCart();

        $this->cart->removeItemBySku($sku);

        $this->cart->toSession();

        return true;
    }

    /**
     * Updates the cart item product quantity
     * If the operation is successful, null will be returned
     * A string error message will be returned on product active/stock errors
     *
     * @param  Product  $product
     * @param  int  $quantity
     *
     * @return string|null
     */
    public function updateCartItemProductQuantity(
        Product $product,
        int $quantity
    ): ?string {
        // todo: refactor
        $error = null;

        if ($quantity < 0) {
            $error = 'Invalid quantity value.';

        } else {
            if (!$product->isActive() || $product->getStock() < $quantity) {
                // todo - confirm isActive check
                $message = 'The quantity can not be updated.';
                $message .= (is_object($product)
                    && get_class($product) == Product::class)
                    ? ' The product stock('.$product->getStock()
                    .') is smaller than the quantity you\'ve selected('
                    .$quantity.')' : '';

                $error = $message;

            } else {
                $this->refreshCart();

                $cartItem = $this->cart->getItemBySku($product->getSku());

                $cartItem->setQuantity($quantity);

                $this->cart->setItem($cartItem)->toSession();
            }
        }

        return $error;
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
        $products
            = $this->productRepository->findBySkus($this->cart->listSkus());

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
        $products
            = $this->productRepository->findBySkus($this->cart->listSkus());

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
        $this->refreshCart();

        $shippingAddress = $this->cart->getShippingAddress();
        $shippingCountry = $shippingAddress ? $shippingAddress->getCountry()
            : '';

        $totalWeight = $this->getTotalCartItemWeight();

        $shippingOption
            = $this->shippingOptionRepository->getShippingCosts($shippingCountry,
            $totalWeight);

        $initialShippingCost = 0;

        if (!empty($shippingOption)) {
            $shippingCost = $shippingOption->getShippingCostsWeightRanges()
                ->first();

            $initialShippingCost = $shippingCost->getPrice();
        }

        return round($initialShippingCost - $this->cart->getShippingDiscountAmount(), 2);
    }

    /**
     * @return float
     */
    public function getProductsDue()
    {
        $this->refreshCart();

        $products
            = $this->productRepository->findBySkus($this->cart->listSkus());

        $totalItemCostDue = 0;

        foreach ($products as $product) {

            /**
             * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            $cartItem = $this->cart->getItemBySku($product->getSku());

            $totalItemCostDue += ($product->getPrice() ?? 0)
                * $cartItem->getQuantity();
        }

        return $totalItemCostDue;
    }

    /**
     * @return float
     */
    public function getTotalItemCostDue()
    {
        $initialProductsDue = $this->getProductsDue();

        $productsDiscountAmount = 0;

        foreach ($this->cart->getItems() as $cartItem) {
            $productsDiscountAmount += $cartItem->getDiscountAmount();
        }

        return round($initialProductsDue - $productsDiscountAmount - $this->cart->getOrderDiscountAmount(), 2);
    }

    /**
     * @return float
     */
    public function getTotalTaxDue()
    {
        $taxableAddress = null;
        $billingAddress = $this->cart->getBillingAddress();

        if ($billingAddress
            && strtolower($billingAddress->getCountry())
            == strtolower(self::TAXABLE_COUNTRY)
        ) {
            $taxableAddress = $billingAddress;
        }

        $shippingAddress = $this->cart->getShippingAddress();

        if (!$taxableAddress && $shippingAddress
            && strtolower($billingAddress->getCountry())
            == strtolower(self::TAXABLE_COUNTRY)
        ) {
            $taxableAddress = $shippingAddress;
        }

        $amountToTax = $this->getTotalItemCostDue() + $this->getTotalShippingDue();

        $result = 0;

        if ($taxableAddress) {
            $result = $this->taxService->vat($amountToTax, $taxableAddress);
        }

        return $result;
    }

    /**
     * @return float
     */
    public function getTotalDue()
    {
        $totalItemCostDue = $this->getTotalItemCostDue();

        $shippingDue = $this->getTotalShippingDue();

        // only item and shipping costs are taxed
        $taxDue = $this->getTotalTaxDue();

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
        $totalItemCostDue = $this->getTotalItemCostDue();

        $shippingDue = $this->getTotalShippingDue();

        // only item and shipping costs are taxed
        $taxDue = $this->getTotalTaxDue();

        if ($this->cart->getPaymentPlanNumberOfPayments() > 1) {
            $financeDue = config('ecommerce.financing_cost_per_order');
        } else {
            $financeDue = 0;
        }

        // Customers can only finance the order item price, taxes, and finance.
        // All shipping must be paid on the first payment.
        $totalToFinance = $totalItemCostDue + $taxDue + $financeDue;

        $initialTotalDueBeforeShipping = round($totalToFinance
            / $this->cart->getPaymentPlanNumberOfPayments(), 2);

        // account for any rounded off cents by adding the difference after all payments to the first payment
        if ($initialTotalDueBeforeShipping
            * $this->cart->getPaymentPlanNumberOfPayments() != $totalToFinance
        ) {
            $initialTotalDueBeforeShipping += abs($initialTotalDueBeforeShipping
                * $this->cart->getPaymentPlanNumberOfPayments()
                - $totalToFinance);
        }

        return $initialTotalDueBeforeShipping + $shippingDue;
    }

    /**
     * Calculate the discounted price on items and the discounted amount on
     * cart(discounts that should be applied on order) and set the discounted
     * price on order item and the total discount amount on cart.
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

    /**
     * @param $productSku
     *
     * @throws ProductNotFoundException
     * @return float
     */
    public function getItemPriceBeforeDiscounts($productSku)
    {
        $product = $this->allProducts[$productSku];

        if (empty($product)) {
            throw new ProductNotFoundException($productSku);
        }

        return $product->getPrice();
    }

    /**
     * @param $productSku
     *
     * @throws ProductNotFoundException
     * @return float
     */
    public function getItemPriceAfterDiscounts($productSku)
    {
        $product = $this->allProducts[$productSku];

        if (empty($product)) {
            throw new ProductNotFoundException($productSku);
        }

        $cartItem = $this->cart->getItemBySku($productSku);

        return $product->getPrice() - $cartItem->getDiscountAmount();
    }

    /**
     * @throws ProductNotFoundException
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
                'sku'                         => $product->getSku(),
                'name'                        => $product->getName(),
                'quantity'                    => $cartItem->getQuantity(),
                'thumbnail_url'               => $product->getThumbnailUrl(),
                'description'                 => $product->getDescription(),
                'stock'                       => $product->getStock(),
                'subscription_interval_type'  => $product->getSubscriptionIntervalType(),
                'subscription_interval_count' => $product->getSubscriptionIntervalCount(),
                'price_before_discounts'      => $this->getItemPriceBeforeDiscounts($cartItem->getSku()),
                'price_after_discounts'       => $this->getItemPriceAfterDiscounts($cartItem->getSku()),
            ];
        }

        $shippingAddress = !empty($this->cart->getShippingAddress())
            ? $this->cart->getShippingAddress()->toArray() : null;
        $billingAddress = !empty($this->cart->getBillingAddress())
            ? $this->cart->getBillingAddress()->toArray() : null;

        $discounts = $this->cart->getCartDiscountNames();

        $numberOfPayments = $this->cart->getPaymentPlanNumberOfPayments();

        $totals = [
            'shipping' => $this->getTotalShippingDue(),
            'tax'      => $this->getTotalTaxDue(),
            'due'      => $this->getDueForInitialPayment(),
        ];

        return [
            'items'              => $items,
            'discounts'          => $discounts,
            'shipping_address'   => $shippingAddress,
            'billing_address'    => $billingAddress,
            'number_of_payments' => $numberOfPayments,
            'totals'             => $totals,
        ];
    }
}
