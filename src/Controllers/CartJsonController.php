<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse as JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Exceptions\Cart\AddToCartException;
use Railroad\Ecommerce\Exceptions\Cart\ProductNotFoundException;
use Railroad\Ecommerce\Exceptions\Cart\UpdateNumberOfPaymentsCartException;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Requests\SessionStoreAddressRequest;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ResponseService;
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
     * ShoppingCartController constructor.
     *
     * @param AddressRepository $addressRepository
     * @param CartAddressService $cartAddressService
     * @param CartService $cartService
     */
    public function __construct(
        AddressRepository $addressRepository,
        CartAddressService $cartAddressService,
        CartService $cartService
    )
    {
        $this->addressRepository = $addressRepository;
        $this->cartAddressService = $cartAddressService;
        $this->cartService = $cartService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $cartArray = $this->cartService->toArray();

        return ResponseService::cart($cartArray)
            ->respond(200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function clear(Request $request)
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
            $this->cartService->getCart()->replaceItems([]);
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

    public function storeAddress(SessionStoreAddressRequest $request)
    {
        $shippingKeys = [
            'shipping-address-line-1' => 'streetLine1',
            'shipping-address-line-2' => 'streetLine2',
            'shipping-city' => 'city',
            'shipping-country' => 'country',
            'shipping-first-name' => 'firstName',
            'shipping-last-name' => 'lastName',
            'shipping-region' => 'state',
            'shipping-zip-or-postal-code' => 'zip',
        ];

        if (!empty($request->get('shipping-address-id'))) {
            $shippingAddressEntity = $this->addressRepository->find($request->get('shipping-address-id'));

            $this->cartAddressService->updateShippingAddress($shippingAddressEntity->toStructure());
        }
        else {
            $requestShippingAddress = $request->only(array_keys($shippingKeys));

            $shippingAddress = $this->cartAddressService->updateShippingAddress(
                Address::createFromArray(
                    array_combine(
                        array_intersect_key($shippingKeys, $requestShippingAddress),
                        $requestShippingAddress
                    )
                )
            );
        }

        $billingKeys = [
            'billing-country' => 'country',
            'billing-region' => 'state',
            'billing-zip-or-postal-code' => 'zip',
            'billing-email' => 'email',
        ];

        if (!empty($request->get('billing-address-id'))) {
            $billingAddressEntity = $this->addressRepository->find($request->get('billing-address-id'));

            $this->cartAddressService->updateBillingAddress($billingAddressEntity->toStructure());
        }
        else {
            $requestBillingAddress = $request->only(array_keys($billingKeys));

            $billingAddress = $this->cartAddressService->updateBillingAddress(
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
}
