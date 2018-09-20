<?php

namespace Railroad\Ecommerce\Controllers;

use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Requests\SessionStoreAddressRequest;
use Railroad\Resora\Entities\Entity;

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
            'shipping-address-line-1' => 'streetLineOne',
            'shipping-address-line-2' => 'streetLineTwo',
            'shipping-city' => 'city',
            'shipping-country' => 'country',
            'shipping-first-name' => 'firstName',
            'shipping-last-name' => 'lastName',
            'shipping-region' => 'region',
            'shipping-zip-or-postal-code' => 'zipOrPostalCode',
        ];

        $requestShippingAddress = $request->only(array_keys($shippingKeys));

        $shippingAddress = $this->cartAddressService->updateAddress(
            array_combine(
                array_intersect_key($shippingKeys, $requestShippingAddress),
                $requestShippingAddress
            ),
            ConfigService::$shippingAddressType
        );

        $billingKeys = [
            'billing-country' => 'country',
            'billing-region' => 'region',
            'billing-zip-or-postal-code' => 'zip',
            'billing-email' => 'email',
        ];

        $requestBillingAddress = $request->only(array_keys($billingKeys));

        $billingAddress = $this->cartAddressService->updateAddress(
            array_combine(
                array_intersect_key($billingKeys, $requestBillingAddress),
                $requestBillingAddress
            ),
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        return reply()->json(
            new Entity([
                'shipping' => $shippingAddress,
                'billing' => $billingAddress
            ]),
            ['code' => 201]
        );
    }
}
