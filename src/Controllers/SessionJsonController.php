<?php

namespace Railroad\Ecommerce\Controllers;

use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Requests\SessionStoreAddressRequest;
use Railroad\Ecommerce\Services\ResponseService;

class SessionJsonController extends BaseController
{
    /**
     * @var \Railroad\Ecommerce\Services\CartAddressService
     */
    private $cartAddressService;

    /**
     * SessionJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Services\CartAddressService $cartAddressService
     */
    public function __construct(CartAddressService $cartAddressService)
    {
        parent::__construct();

        $this->cartAddressService = $cartAddressService;
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
            'shipping-zip-or-postal-code' => 'zipOrPostalCode',
        ];

        $requestShippingAddress = $request->only(array_keys($shippingKeys));

        $shippingAddress = $this->cartAddressService->updateShippingAddress(
            Address::createFromArray(
                array_combine(
                    array_intersect_key($shippingKeys, $requestShippingAddress),
                    $requestShippingAddress
                )
            )
        );

        $billingKeys = [
            'billing-country' => 'country',
            'billing-region' => 'state',
            'billing-zip-or-postal-code' => 'zipOrPostalCode',
            'billing-email' => 'email',
        ];

        $requestBillingAddress = $request->only(array_keys($billingKeys));

        $billingAddress = $this->cartAddressService->updateBillingAddress(
            Address::createFromArray(
                array_combine(
                    array_intersect_key($billingKeys, $requestBillingAddress),
                    $requestBillingAddress
                )
            )
        );

        return ResponseService::sessionAddresses(
            $billingAddress,
            $shippingAddress
        );
    }
}
