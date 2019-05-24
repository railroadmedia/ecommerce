<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Requests\SessionStoreAddressRequest;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ResponseService;

// todo: I think this controller can probably be removed,
// todo: the update address endpoint should go in the cart json controller
class SessionJsonController extends Controller
{
    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * SessionJsonController constructor.
     *
     * @param CartAddressService $cartAddressService
     * @param CartService $cartService
     * @param AddressRepository $addressRepository
     */
    public function __construct(
        CartAddressService $cartAddressService,
        CartService $cartService,
        AddressRepository $addressRepository
    )
    {
        $this->cartAddressService = $cartAddressService;
        $this->cartService = $cartService;
        $this->addressRepository = $addressRepository;
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
