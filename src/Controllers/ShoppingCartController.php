<?php

namespace Railroad\Ecommerce\Controllers;

use Doctrine\ORM\EntityManager;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Repositories\ProductRepository;
// use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Resora\Entities\Entity;

class ShoppingCartController extends BaseController
{
    /**
     * @var EntityManager
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
     * @var TaxService
     */
    private $taxService;

    /**
     * @var ShippingOptionRepository
     */
    // private $shippingOptionsRepository;

    /**
     * ShoppingCartController constructor.
     *
     * @param \Railroad\Ecommerce\Services\CartService $cartService
     * @param \Railroad\Ecommerce\Repositories\ProductRepository $productRepository
     * @param \Railroad\Ecommerce\Services\CartAddressService $cartAddressService
     * @param \Railroad\Ecommerce\Services\TaxService $taxService
     */
    public function __construct(
        CartService $cartService,
        // ProductRepository $productRepository,
        CartAddressService $cartAddressService,
        EntityManager $entityManager,
        PaymentPlanService $paymentPlanService,
        TaxService $taxService //,
        // ShippingOptionRepository $shippingOptionRepository
    ) {
        parent::__construct();

        $this->cartService = $cartService;
        // $this->productRepository = $productRepository;
        $this->cartAddressService = $cartAddressService;
        $this->entityManager = $entityManager;
        $this->paymentPlanService = $paymentPlanService;
        $this->productRepository = $this->entityManager
                                        ->getRepository(Product::class);
        $this->taxService = $taxService;
        // $this->shippingOptionsRepository = $shippingOptionRepository;
    }

    /** Add products to cart; if the products are active and available(the product stock > requested quantity).
     *  The success field from response it's set to false if at least one product it's not active or available.
     *
     * @param Request $request
     * @return array
     */
    public function addToCart(Request $request)
    {
        $input = $request->all();
        $errors = [];
        $addedProducts = [];
        $success = false;
        $redirect = false;

        if (!empty($input['locked']) && $input['locked'] == "true") {
            $this->cartService->lockCart();
        } elseif ($this->cartService->isLocked()) {
            $this->cartService->unlockCart();
        }

        if (array_key_exists('redirect', $input) && $input['redirect'] == "/shop") {
            $redirect = $input['redirect'];
        }

        //if the promo code exists on the requests, set it on the session
        if (!empty($input['promo-code'])) {
            $this->cartService->setPromoCode($input['promo-code']);
        } else {
            $this->cartService->setPromoCode(null);
        }

        if (!empty($input['products'])) {
            $products = $input['products'];
            $cart = $this->cartService->getCart();

            foreach ($products as $productSku => $productInfo) {
                $productInfo = explode(',', $productInfo);

                $quantityToAdd = $productInfo[0];

                if (!empty($productInfo[1])) {
                    $subscriptionType = !empty($productInfo[1]) ? $productInfo[1] : null;
                    $subscriptionFrequency = !empty($productInfo[2]) ? $productInfo[2] : null;
                }

                $product = $this->productRepository
                                ->findOneBySku($productSku);
                /*
                $product =
                    $this->productRepository->query()
                        ->where(['sku' => $productSku])
                        ->first();
                */

                // if ($product && ($product['stock'] === null || $product['stock'] >= $quantityToAdd)) {
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
                    /*
                    $cart = $this->cartService->addCartItem(
                        $product['name'],
                        $product['description'],
                        $quantityToAdd,
                        $product['price'],
                        $product['is_physical'],
                        $product['is_physical'],
                        $product['subscription_interval_type'],
                        $product['subscription_interval_count'],
                        [
                            'product-id' => $product['id'],
                            'requires-shipping-address' => $product['is_physical'],
                            'thumbnail_url' => $product['thumbnail_url'],
                            'is_physical' => $product['is_physical'],
                        ]
                    );
                    */
                } else {
                    $message = 'Product with SKU:' . $productSku . ' could not be added to cart.';
                    $message .= (!is_null($product)) ?
                        ' The product stock(' .
                        $product['stock'] .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantityToAdd .
                        ')' : '';
                    $errors[] = ['message' => $message, 'product' => $product];
                }
            }
        }

        $billingAddress = $this->cartAddressService
            ->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);

        $request->session()->flash('addedProducts', $addedProducts);
        $request->session()->flash('cartSubTotal', $cart->getTotalDue());
        $request->session()->flash(
            'cartNumberOfItems',
            count($cart->getItems())
        );
        $request->session()->flash('notAvailableProducts', $errors);

        /** @var \Illuminate\Http\RedirectResponse $redirectResponse */
        $redirectResponse = $request->get('redirect') ?
            redirect()->away($request->get('redirect')) :
            redirect()->back();

        $redirectResponse->with('success', $success);

        return $redirectResponse;

        // $response = [
        //     'addedProducts' => $addedProducts,
        //     'cartSubTotal' => $cart->getTotalDue(),
        //     'cartNumberOfItems' => count($cart->getItems()),
        //     'notAvailableProducts' => $errors,
        // ];

        // if (!empty($input['redirect'])) {
        //     return reply()->form(
        //         [$success],
        //         $input['redirect'],
        //         [],
        //         $response
        //     );
        // }

        // return reply()->form(
        //     [$success],
        //     null,
        //     [],
        //     $response
        // );
    }

    /** Remove product from cart.
     *
     * @param int $productId
     * @return JsonResponse
     */
    public function removeCartItem($productId)
    {
        $cartItems =
            $this->cartService->getCart()
                ->getItems();

        foreach ($cartItems as $cartItem) {
            if ($cartItem->getOptions()['product-id'] == $productId) {
                $this->cartService->removeCartItem($cartItem->id);
                $shippingCosts = $this->shippingOptionsRepository->getShippingCosts(
                        $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE)['country'],
                        $this->cartService->getCart()
                            ->getTotalWeight()
                    )['price'] ?? 0;

                $this->cartService->getCart()
                    ->setShippingCosts($shippingCosts);
            }
        }

        return reply()->json(
            new Entity($this->getCartData()),
            [
                'code' => 201,
            ]
        );
    }

    /** Update the cart item quantity.
     * If the product it's not active or it's not available(the product stock it's smaller that the quantity)
     * an error message it's returned in notAvailableProducts, success = false and the cart item quantity it's not
     * modified.
     *
     * @param int $productId
     * @param int $quantity
     * @return JsonResponse
     */
    public function updateCartItemQuantity($productId, $quantity)
    {
        $product =
            $this->productRepository->query()
                ->where(['id' => $productId])
                ->first();
        $errors = [];
        $success = false;

        if (($product) && ($product['stock'] >= $quantity)) {
            if ($quantity >= 0) {
                $success = true;
                $cartItems = $this->cartService->getAllCartItems();

                foreach ($cartItems as $cartItem) {
                    if ($cartItem->getOptions()['product-id'] == $productId) {
                        if ($quantity > 0) {
                            $this->cartService->updateCartItemQuantity($cartItem->getId(), $quantity);
                            $shippingCosts = $this->shippingOptionsRepository->getShippingCosts(
                                    $this->cartAddressService->getAddress(
                                        CartAddressService::SHIPPING_ADDRESS_TYPE
                                    )['country'],
                                    $this->cartService->getCart()
                                        ->getTotalWeight()
                                )['price'] ?? 0;

                            $this->cartService->getCart()
                                ->setShippingCosts($shippingCosts);
                        } else {
                            $this->cartService->removeCartItem($cartItem->getId());
                        }
                    }
                }
            } else {
                $errors = ['Invalid quantity value.'];
            }
        } else {
            $message = 'The quantity can not be updated.';
            $message .= (is_object($product) && get_class($product) == Entity::class) ?
                ' The product stock(' .
                $product['stock'] .
                ') is smaller than the quantity you\'ve selected(' .
                $quantity .
                ')' : '';
            $errors[] = $message;
        }

        $data = array_merge(
            [
                'success' => $success,
                'addedProducts' => $this->cartService->getAllCartItems(),
                'cartNumberOfItems' => count($this->cartService->getAllCartItems()),
                'notAvailableProducts' => $errors,
            ],
            $this->getCartData()
        );

        return reply()->json(
            new Entity($data),
            [
                'code' => 201,
            ]
        );
    }

    /**
     * @return array
     */
    protected function getCartData()
    {
        $cartData = [
            'tax' => 0,
            'total' => 0,
            'cartItems' => [],
        ];

        $cartItems = $this->cartService->getAllCartItems();

        if (count($cartItems)) {
            $isPaymentPlanEligible = $this->paymentPlanService->isPaymentPlanEligible();

            $paymentPlanPricing = $this->paymentPlanService->getPaymentPlanPricingForCartItems();

            $cartData['tax'] =
                $this->cartService->getCart()
                    ->calculateTaxesDue();
            $cartData['total'] =
                $this->cartService->getCart()
                    ->getTotalDue();
            $cartData['cartItems'] =
                $this->cartService->getCart()
                    ->getItems();
            $cartData['isPaymentPlanEligible'] = $isPaymentPlanEligible;
            $cartData['paymentPlanPricing'] = $paymentPlanPricing;
        }

        return $cartData;
    }
}
