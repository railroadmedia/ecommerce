<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Resora\Decorators\DecoratorInterface;

class PaymentMethodBillingAddressDecorator implements DecoratorInterface
{
    /**
     * @var AddressRepository
     */
    private $addressRepository;

    public function __construct(AddressRepository $addressRepository)
    {
        $this->addressRepository = $addressRepository;
    }

    public function decorate($paymentMethods)
    {
        $addressIds = [];

        foreach ($paymentMethods as $index => $paymentMethod) {
            $addressIds[] = $paymentMethod['billing_address_id'];
        }

        $addresses = $this->addressRepository->query()->whereIn('id', $addressIds)->get()->keyBy('id');

        foreach ($paymentMethods as $index => $paymentMethod) {
            $address = $addresses[$paymentMethod['billing_address_id']] ?? null;

            if (!empty($address)) {
                $paymentMethods[$index]['billing_address'] = (array)$address;
            }
        }

        return $paymentMethods;
    }
}