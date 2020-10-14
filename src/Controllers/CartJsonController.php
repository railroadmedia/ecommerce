<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse as JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Address as AddressEntity;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Exceptions\Cart\AddToCartException;
use Railroad\Ecommerce\Exceptions\Cart\ProductNotFoundException;
use Railroad\Ecommerce\Exceptions\Cart\UpdateNumberOfPaymentsCartException;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Requests\SessionStoreAddressRequest;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class CartJsonController extends Controller
{
    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * ShoppingCartController constructor.
     *
     * @param AddressRepository $addressRepository
     * @param CartAddressService $cartAddressService
     * @param CartService $cartService
     * @param PermissionService $permissionService
     */
    public function __construct(
        AddressRepository $addressRepository,
        CartAddressService $cartAddressService,
        CartService $cartService,
        PermissionService $permissionService
    )
    {
        $this->addressRepository = $addressRepository;
        $this->cartAddressService = $cartAddressService;
        $this->cartService = $cartService;
        $this->permissionService = $permissionService;
    }

    /**
     * @return JsonResponse
     * @throws Throwable
     */
    public function index()
    {
        $cartArray = $this->cartService->toArray();

        return ResponseService::cart($cartArray)
            ->respond(200);
    }

    /**
     * @return JsonResponse
     * @throws Throwable
     */
    public function clear()
    {
        $this->cartService->clearCart();

        $cartArray = $this->cartService->toArray();

        return ResponseService::cart($cartArray)
            ->respond(200);
    }

    /**
     * Add products to cart; if the products are active and available (the
     * product stock > requested quantity).
     * Errors are set in $response['meta']['cart']['errors']
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Throwable
     *
     */
    public function addCartItem(Request $request)
    {
        $errors = [];

        $this->cartService->refreshCart();

        if ($request->get('locked', false) == true) {
            $this->cartService->clearCart();
        }

        foreach ($request->get('products', []) as $productSku => $quantityToAdd) {

            try {
                $product = $this->cartService->addToCart(
                    $productSku,
                    $quantityToAdd,
                    $request->get('locked', false) == 'true',
                    $request->get('promo-code', '')
                );
            } catch (AddToCartException $addToCartException) {
                $errors[] = $addToCartException->getMessage();
                continue;
            }

            if (empty($product)) {
                $errors[] = 'Error adding product SKU ' . $productSku . ' to the cart.';
            }
        }

        $cartArray = $this->cartService->toArray();

        if (!empty($errors)) {
            $cartArray['errors'] = $errors;

            return ResponseService::cart($cartArray)
                ->respond(403);
        }

        return ResponseService::cart($cartArray)
            ->respond(201);
    }

    /**
     * Remove product from cart.
     *
     * @param string $productSku
     *
     * @return JsonResponse
     * @throws Throwable
     *
     */
    public function removeCartItem(string $productSku)
    {
        $errors = [];

        try {
            $this->cartService->removeFromCart($productSku);
        } catch (ProductNotFoundException $exception) {
            $errors[] = $exception->getMessage();
        }

        $cartArray = $this->cartService->toArray();

        if (!empty($errors)) {
            $cartArray['errors'] = $errors;
        }

        return ResponseService::cart($cartArray)
            ->respond(200);
    }

    /**
     * Update the cart item quantity; if the product is active and available (the
     * product stock > requested quantity).
     * Errors are set in $response['meta']['cart']['errors']
     *
     * @param string $productSku
     * @param int $quantity
     *
     * @return JsonResponse
     * @throws Throwable
     *
     */
    public function updateCartItemQuantity(string $productSku, int $quantity)
    {
        $errors = [];

        try {
            $this->cartService->updateCartQuantity($productSku, $quantity);
        } catch (AddToCartException $addToCartException) {
            $errors[] = $addToCartException->getMessage();
        }

        $cartArray = $this->cartService->toArray();

        if (!empty($errors)) {
            $cartArray['errors'] = $errors;
        }

        return ResponseService::cart($cartArray)
            ->respond(200);
    }

    /**
     * Update number of payments
     *
     * @param int $numberOfPayments
     *
     * @return JsonResponse
     * @throws Throwable
     *
     */
    public function updateNumberOfPayments(int $numberOfPayments)
    {
        $errors = [];

        try {
            $this->cartService->updateNumberOfPayments($numberOfPayments);
        } catch (UpdateNumberOfPaymentsCartException $updateNumberOfPaymentsCartException) {
            $errors[] = $updateNumberOfPaymentsCartException->getMessage();
        }

        $cartArray = $this->cartService->toArray();

        if (!empty($errors)) {
            $cartArray['errors'] = $errors;
        }

        return ResponseService::cart($cartArray)
            ->respond(200);
    }

    /**
     * @param SessionStoreAddressRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function storeAddress(SessionStoreAddressRequest $request)
    {
        $shippingKeys = [
            'shipping_address_line_1' => 'streetLine1',
            'shipping_address_line_2' => 'streetLine2',
            'shipping_city' => 'city',
            'shipping_country' => 'country',
            'shipping_first_name' => 'firstName',
            'shipping_last_name' => 'lastName',
            'shipping_region' => 'region',
            'shipping_zip_or_postal_code' => 'zip',
        ];

        if (!empty($request->get('shipping_address_id'))) {
            /** @var $shippingAddressEntity AddressEntity */
            $shippingAddressEntity = $this->addressRepository->find($request->get('shipping_address_id'));

            $this->cartAddressService->updateShippingAddress($shippingAddressEntity->toStructure());
        }
        else {
            $requestShippingAddress = $request->only(array_keys($shippingKeys));

            $this->cartAddressService->updateShippingAddress(
                Address::createFromArray(
                    array_combine(
                        array_intersect_key($shippingKeys, $requestShippingAddress),
                        $requestShippingAddress
                    )
                )
            );
        }

        $billingKeys = [
            'billing_country' => 'country',
            'billing_region' => 'region',
            'billing_zip_or_postal_code' => 'zip',
            'billing_email' => 'email',
        ];

        if (!empty($request->get('billing_address_id'))) {
            /** @var $billingAddressEntity AddressEntity */
            $billingAddressEntity = $this->addressRepository->find($request->get('billing_address_id'));

            $this->cartAddressService->updateBillingAddress($billingAddressEntity->toStructure());
        }
        else {
            $requestBillingAddress = $request->only(array_keys($billingKeys));

            $this->cartAddressService->updateBillingAddress(
                Address::createFromArray(
                    array_combine(
                        array_intersect_key($billingKeys, $requestBillingAddress),
                        $requestBillingAddress
                    )
                )
            );
        }

        return ResponseService::cart($this->cartService->toArray())
            ->respond(200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function updateTotalOverrides(Request $request)
    {
        $this->cartService->refreshCart();
        $cart = $this->cartService->getCart();

        if ($this->permissionService->can(auth()->id(), 'place-orders-for-other-users')) {

            $cart->setShippingOverride($request->get('shipping_due_override'));

            $overrides = $request->get('order_items_due_overrides', []);

            if (!empty($overrides) && is_array($overrides)) {
                foreach ($overrides as $override) {
                    foreach ($cart->getItems() as $cartItem) {
                        if ($override['sku'] == $cartItem->getSku() && !is_null($override['amount'])) {
                            $cartItem->setDueOverride($override['amount']);
                        }
                    }
                }
            }
        }

        $this->cartService->setCart($cart);

        $cart->toSession();

        return ResponseService::cart($this->cartService->toArray())
            ->respond(200);
    }
}
