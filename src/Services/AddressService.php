<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;

class AddressService
{
    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    CONST BILLING_ADDRESS = 'billing';
    CONST SHIPPING_ADDRESS = 'shipping';

    /**
     * AddressService constructor.
     * @param $addressRepository
     */
    public function __construct(AddressRepository $addressRepository, OrderRepository $orderRepository)
    {
        $this->addressRepository = $addressRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param $type
     * @param $brand
     * @param $userId
     * @param $customerId
     * @param $firstName
     * @param $lastName
     * @param $streetLine1
     * @param $streetLine2
     * @param $city
     * @param $zip
     * @param $state
     * @param $country
     * @return array|null
     */
    public function store($type, $brand, $userId, $customerId, $firstName, $lastName, $streetLine1, $streetLine2, $city, $zip, $state, $country)
    {
        AddressRepository::$availableUserId = $userId;
        AddressRepository::$availableCustomerId = $customerId;

        $addressId = $this->addressRepository->create(
            [
                'type' => $type,
                'brand' => $brand ?? ConfigService::$brand,
                'user_id' => $userId,
                'customer_id' => $customerId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'street_line_1' => $streetLine1,
                'street_line_2' => $streetLine2,
                'city' => $city,
                'zip' => $zip,
                'state' => $state,
                'country' => $country,
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
        );

        return $this->getById($addressId);
    }

    /**
     * @param $addressId
     * @return array|null
     */
    public function getById($addressId)
    {
        return $this->addressRepository->getById($addressId);
    }

    /**
     * @param $id
     * @param array $data
     * @return array|null
     */
    public function update($id, array $data)
    {
        $address = $this->getById($id);

        if (empty($address)) {
            return null;
        }

        $data['updated_on'] = Carbon::now()->toDateTimeString();
        $this->addressRepository->update($id, $data);

        return $this->getById($id);
    }

    /**
     * @param $id
     * @param $userId
     * @param $customerId
     * @return bool|int|null
     */
    public function delete($id, $userId, $customerId)
    {
        AddressRepository::$availableUserId = $userId;
        AddressRepository::$availableCustomerId = $customerId;

        $address = $this->getById($id);

        if (empty($address)) {
            return null;
        }

        $orders = $this->orderRepository->getOrdersByConditions([$address['type'] . '_address_id' => $address['id']]);
        if (!empty($orders)) {
            return -1;
        }

        return $this->addressRepository->delete($id);
    }
}