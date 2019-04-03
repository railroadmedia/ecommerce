<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\AddCartItemsResult;
use Railroad\Ecommerce\Entities\Structures\Cart;
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
    }

    /**
     * Add products to cart; if the products are active and available(the product stock > requested quantity).
     * The success field from response it's set to false if at least one product it's not active or available.
     *
     * @param Request $request
     *
     * @return AddCartItemResult
     */
    public function addToCart(Request $request): AddCartItemsResult
    {
        $result = new AddCartItemsResult();

        $result->setSuccess(true);

        $input = $request->all();

        $cart = Cart::fromSession();

        // cart locking
        if (!empty($input['locked']) && $input['locked'] == "true") {
            $cart->setLocked(true);
        } elseif ($cart->getLocked()) {

            // if the cart is locked and a new item is added, we should wipe it first
            $cart = new Cart();
            $cart->toSession();
        }

        // promo code
        if (!empty($input['promo-code'])) {
            $cart->setPromoCode($input['promo-code']);
        }

        // products
        if (!empty($input['products'])) {
            $products = $input['products'];

            foreach ($products as $productSku => $productInfo) {
                $productInfo = explode(',', $productInfo); // todo - clarify where the rest of the $productInfo is used

                $quantityToAdd = $productInfo[0];

                $product = $this->productRepository->findOneBySku($productSku);

                if ($product && $product->isActive() && ($product->getStock() === null || $product->getStock() >= $quantityToAdd)) {
                    // todo - confirm isActive check
                    $cart->setItem(new CartItem($productSku, $quantityToAdd));

                    $result->addProduct($product);

                } else {
                    $result->setSuccess(false);

                    $message = 'Product with SKU:' . $productSku . ' could not be added to cart.';
                    $message .= (!is_null($product)) ?
                        ' The product stock(' .
                        $product->getStock() .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantityToAdd .
                        ')' : '';

                    $result->addError(['message' => $message, 'product' => $product]);
                }
            }
        }

        // save the cart to the session
        $cart->toSession();

        return $result;
    }

    /**
     * Removes the cart item, if the product is in cart
     *
     * @param Product $product
     */
    public function removeProductFromCart(Product $product)
    {
        $this->refreshCart();

        if ($this->cart->getItemBySku($product->getSku())) {
            $this->cart->removeCartItemBySku($product->getSku());
        }

        $this->cart->toSession();
    }

    /**
     * Updates the cart item product quantity
     * If the operation is successful, null will be returned
     * A string error message will be returned on product active/stock errors
     *
     * @param Product $product
     * @param int $quantity
     *
     * @return string|null
     */
    public function updateCartItemProductQuantity(Product $product, int $quantity): ?string
    {
        $error = null;

        if ($quantity < 0) {
            $error = 'Invalid quantity value.';

        } else if (!$product->isActive() || $product->getStock() < $quantity) {
            // todo - confirm isActive check
            $message = 'The quantity can not be updated.';
            $message .= (is_object($product) && get_class($product) == Product::class) ?
                ' The product stock(' .
                $product->getStock() .
                ') is smaller than the quantity you\'ve selected(' .
                $quantity .
                ')' : '';

            $error = $message;

        } else {
            $this->refreshCart();

            $cartItem = $this->cart->getItemBySku($product->getSku());

            $cartItem->setQuantity($quantity);

            $this->cart
                ->setItem($cartItem)
                ->toSession();
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
        $products = $this->productRepository->findBySkus(['sku' => $this->cart->listSkus()]);

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
        $products = $this->productRepository->findBySkus(['sku' => $this->cart->listSkus()]);

        $totalWeight = 0;

        foreach ($products as $product) {
            $totalWeight += $product->getWeight() ?? 0;
        }

        return $totalWeight;
    }

    /**
     * @return float
     */
    public function getInitialShippingDue()
    {
        $this->refreshCart();

        $shippingAddress = $this->cart->getShippingAddress();
        $shippingCountry = $shippingAddress ? $shippingAddress->getCountry() : '';

        $totalWeight = $this->getTotalCartItemWeight();

        $shippingOption = $this->shippingOptionRepository->getShippingCosts($shippingCountry, $totalWeight);

        $initialShippingCost = 0;

        if (!empty($shippingOption)) {
            $shippingCost = $shippingOption->getShippingCostsWeightRanges()
                ->first();

            $initialShippingCost = $shippingCost->getPrice();
        }

        return $initialShippingCost;
    }

    /**
     * @return float
     */
    public function getTotalShippingDue()
    {
        $initialShippingCosts = $this->getInitialShippingDue();

        return $this->discountService->getDiscountedShippingDue($initialShippingCosts);
    }

    /**
     * @return float
     */
    public function getProductsDue()
    {
        $this->refreshCart();

        $products = $this->productRepository->findBySkus(['sku' => $this->cart->listSkus()]);

        $totalItemCostDue = 0;

        foreach ($products as $product) {

            /**
             * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            $cartItem = $this->cart->getItemBySku($product->getSku());

            $totalItemCostDue += ($product->getPrice() ?? 0) * $cartItem->getQuantity();
        }

        return $totalItemCostDue;
    }

    /**
     * @return float
     */
    public function getTotalItemCostDue()
    {
        $initialProductsDue = $this->getProductsDue();

        return $this->discountService->getDiscountedItemsCostDue($initialProductsDue);
    }

    /**
     * @return float
     */
    public function getTotalTaxDue()
    {
        // todo - clarify if the tax applies to initial products/shipping costs or to discounted products/shipping costs

        $taxableAddress = null;
        $billingAddress = $this->cart->getBillingAddress();

        if ($billingAddress && strtolower($billingAddress->getCountry()) == strtolower(self::TAXABLE_COUNTRY)) {
            $taxableAddress = $billingAddress;
        }

        $shippingAddress = $this->cart->getShippingAddress();

        if (!$taxableAddress && $shippingAddress && strtolower($billingAddress->getCountry()) == strtolower(self::TAXABLE_COUNTRY)) {
            $taxableAddress = $shippingAddress;
        }

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
        $totalItemCostDue = $this->getDiscountedProductsDue();

        // todo: apply product discounts and subtract from $totalItemCostDue - should be applied in getDiscountedProductsDue

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
        $totalItemCostDue = $this->getDiscountedProductsDue();

        $shippingDue = $this->getTotalShippingDue();

        // todo: apply product discounts and subtract from $totalItemCostDue - should be applied in getDiscountedProductsDue

        // only item and shipping costs are taxed
        $taxDue = $this->getTotalTaxDue($totalItemCostDue + $shippingDue);

        if ($this->cart->getPaymentPlanNumberOfPayments() > 1) {
            $financeDue = config('ecommerce.financing_cost_per_order');
        } else {
            $financeDue = 0;
        }

        // Customers can only finance the order item price, taxes, and finance.
        // All shipping must be paid on the first payment.
        $totalToFinance = $totalItemCostDue + $taxDue + $financeDue;

        $initialTotalDueBeforeShipping = round($totalToFinance / $this->cart->getPaymentPlanNumberOfPayments(), 2);

        // account for any rounded off cents by adding the difference after all payments to the first payment
        if ($initialTotalDueBeforeShipping * $this->cart->getPaymentPlanNumberOfPayments() != $totalToFinance) {
            $initialTotalDueBeforeShipping += abs($initialTotalDueBeforeShipping *
                $this->cart->getPaymentPlanNumberOfPayments() - $totalToFinance);
        }

        return $initialTotalDueBeforeShipping + $shippingDue;
    }

    /**
     * Calculate the discounted price on items and the discounted amount on cart(discounts that should be applied on
     * order) and set the discounted price on order item and the total discount amount on cart.
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
}
