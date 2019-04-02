<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\ResponseService;
use Spatie\Fractal\Fractal;

class ShoppingCartJsonController extends BaseController
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var PaymentPlanService
     */
    private $paymentPlanService;

    /**
     * ShoppingCartController constructor.
     *
     * @param CartService $cartService
     * @param CartAddressService $cartAddressService
     * @param EcommerceEntityManager $entityManager
     * @param PaymentPlanService $paymentPlanService
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CartService $cartService,
        CartAddressService $cartAddressService,
        EcommerceEntityManager $entityManager,
        PaymentPlanService $paymentPlanService,
        ProductRepository $productRepository
    ) {
        parent::__construct();

        $this->cartService = $cartService;
        $this->cartAddressService = $cartAddressService;
        $this->entityManager = $entityManager;
        $this->paymentPlanService = $paymentPlanService;
        $this->productRepository = $productRepository;
    }

    /**
     * Add products to cart; if the products are active and available(the product stock > requested quantity).
     * The success field from response it's set to false if at least one product it's not active or available.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function addToCart(Request $request)
    {
        $input = $request->all();
        $errors = [];
        $addedProducts = [];
        $success = false;

        if (!empty($input['locked']) && $input['locked'] == "true") {
            $this->cartService->lockCart();
        } elseif ($this->cartService->isLocked()) {
            $this->cartService->unlockCart();
        }

        // if the promo code exists on the requests, set it on the session
        if (!empty($input['promo-code'])) {
            $this->cartService->setPromoCode($input['promo-code']);
        }

        $cart = $this->cartService->getCart();

        if (!empty($input['products'])) {
            $products = $input['products'];

            foreach ($products as $productSku => $productInfo) {
                $productInfo = explode(',', $productInfo);

                $quantityToAdd = $productInfo[0];

                $product = $this->productRepository->findOneBySku($productSku);

                if (
                    $product &&
                    (
                        $product->getStock() === null ||
                        $product->getStock() >= $quantityToAdd
                    )
                ) {
                    $success = true;
                    $addedProducts[] = $product;
                    $cart = $this->cartService->addCartItem(
                        $product->getName(),
                        $product->getDescription(),
                        $quantityToAdd,
                        $product->getPrice(),
                        $product->getIsPhysical(),
                        $product->getIsPhysical(),
                        $product->getSubscriptionIntervalType(),
                        $product->getSubscriptionIntervalCount(),
                        [
                            'product-id' => $product->getId(),
                            'requires-shipping-address' => $product->getIsPhysical(),
                            'thumbnail_url' => $product->getThumbnailUrl(),
                            'is_physical' => $product->getIsPhysical(),
                        ]
                    );

                } else {
                    $message = 'Product with SKU:' . $productSku . ' could not be added to cart.';
                    $message .= (!is_null($product)) ?
                        ' The product stock(' .
                        $product->getStock() .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantityToAdd .
                        ')' : '';
                    $errors[] = ['message' => $message, 'product' => $product];
                }
            }
        }

        /** @var \Illuminate\Http\RedirectResponse $redirectResponse */
        $redirectResponse = $request->get('redirect') ?
            redirect()->away($request->get('redirect')) :
            redirect()->back();

        $redirectResponse->with('success', $success);
        $redirectResponse->with('addedProducts', $addedProducts);
        $redirectResponse->with('cartSubTotal', $cart->getTotalDue());
        $redirectResponse->with('cartNumberOfItems', count($cart->getItems()));
        $redirectResponse->with('notAvailableProducts', $errors);

        return $redirectResponse;
    }

    /**
     * Remove product from cart.
     *
     * @param int $productId
     *
     * @return Fractal
     */
    public function removeCartItem($productId)
    {
        $cartItems = $this->cartService->getCart()->getItems();

        foreach ($cartItems as $cartItem) {
            /**
             * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            if ($cartItem->getOptions()['product-id'] == $productId) {
                $this->cartService->removeCartItem($cartItem->id);
                $this->cartService->calculateShippingCosts();
            }
        }

        $cartItems = $this->cartService->getCart()->getItems();
        $cartMetaData = $this->getCartData();

        return ResponseService::cartData($cartItems, $cartMetaData);
    }

    /**
     * Update the cart item quantity.
     * If the product it's not active or it's not available(the product stock it's smaller that the quantity)
     * an error message it's returned in notAvailableProducts, success = false and the cart item quantity it's not
     * modified.
     *
     * @param int $productId
     * @param int $quantity
     *
     * @return Fractal
     */
    public function updateCartItemQuantity($productId, $quantity)
    {
        $product = $this->productRepository->find($productId);

        $errors = [];
        $success = true;

        if ($quantity < 0) {
            $errors = ['Invalid quantity value.'];
            $success = false;
        }

        if ($success && (!$product || $product->getStock() < $quantity)) {
            $message = 'The quantity can not be updated.';
            $message .= (is_object($product) && get_class($product) == Product::class) ?
                ' The product stock(' .
                $product->getStock() .
                ') is smaller than the quantity you\'ve selected(' .
                $quantity .
                ')' : '';
            $errors[] = $message;
            $success = false;
        }

        if ($success) {

            $cartItems = $this->cartService->getAllCartItems();

            foreach ($cartItems as $cartItem) {
                /**
                 * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
                 */
                if ($cartItem->getOptions()['product-id'] == $productId) {
                    if ($quantity > 0) {
                        $this->cartService
                            ->updateCartItemQuantity(
                                $cartItem->getId(),
                                $quantity
                            );
                    } else {
                        $this->cartService->removeCartItem($cartItem->getId());
                    }

                    $this->cartService->calculateShippingCosts();
                }
            }
        }

        $cartItems = $this->cartService->getAllCartItems();

        $cartMetaData = array_merge(
            [
                'success' => $success,
                'cartNumberOfItems' => count($cartItems),
                'notAvailableProducts' => $errors,
            ],
            $this->getCartData()
        );

        return ResponseService::cartData($cartItems, $cartMetaData);
    }

    /**
     * @return array
     */
    protected function getCartData()
    {
        $cartData = [
            'tax' => 0,
            'total' => 0,
        ];

        $cartItems = $this->cartService->getAllCartItems();

        if (count($cartItems)) {

            $cart = $this->cartService->getCart();

            $cartData['tax'] = $cart->calculateTaxesDue();
            $cartData['total'] = $cart->getTotalDue();

            $isPaymentPlanEligible = $this->paymentPlanService
                ->isPaymentPlanEligible();

            $paymentPlanPricing = $this->paymentPlanService
                ->getPaymentPlanPricingForCartItems();

            $cartData['isPaymentPlanEligible'] = $isPaymentPlanEligible;
            $cartData['paymentPlanPricing'] = $paymentPlanPricing;
        }

        return $cartData;
    }
}
