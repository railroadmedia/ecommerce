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

    //Constants for address types
    CONST BILLING_ADDRESS = 'billing';
    CONST SHIPPING_ADDRESS = 'shipping';

    /**
     * AddressService constructor.
     * @param AddressRepository $addressRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        AddressRepository $addressRepository,
        OrderRepository $orderRepository
    ) {
        $this->addressRepository = $addressRepository;
        $this->orderRepository = $orderRepository;
    }

    /** Call the method that save a new address record in the database.
     * Return an array with the new created address.
     * @param string $type
     * @param string|null $brand
     * @param int|null $userId
     * @param int|null $customerId
     * @param string $firstName
     * @param string $lastName
     * @param string $streetLine1
     * @param string|null $streetLine2
     * @param string $city
     * @param string $zip
     * @param string $state
     * @param string $country
     * @return array|null
     */
    public function store($type, $brand, $userId, $customerId, $firstName, $lastName, $streetLine1, $streetLine2, $city, $zip, $state, $country)
    {

        $addressId = $this->addressRepository->create(
            [
                'type' => $type,
                'brand' => $brand ?? ConfigService::$brand,
                'user_id' => $userId ,
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

    /** Return an array with the address data based on the id
     * @param int $addressId
     * @return array|null
     */
    public function getById($addressId)
    {
        return $this->addressRepository->getById($addressId);
    }

    /** Call the method that update address record if the address exist in the database
     * Return: - null if the address doesn't exist in the database
     *         - an array with the updated address
     * @param int $id
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

    /** Call the method that delete the address record from the database, if the address exists and it's not assigned to any orders.
     * Return  - null if the address not exists or the user have not rights to access it
     *         - -1 if the adress it's associated to orders
     *          - boolean otherwise
     * @param int $id
     * @return bool|int|null
     */
    public function delete($id)
    {
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