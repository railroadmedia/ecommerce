<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\OrderRepository;

class OrderService
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * OrderService constructor.
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /** Call the method that create an order record in the database and return an array with the new created order data
     * @param $due
     * @param $tax
     * @param $shippingCosts
     * @param $paid
     * @param $userId
     * @param $customerId
     * @param $shippingAddressId
     * @param $billingAddressId
     * @param null $brand
     * @return array
     */
    public function store($due, $tax, $shippingCosts, $paid, $userId, $customerId, $shippingAddressId, $billingAddressId, $brand = null)
    {
        $orderId = $this->orderRepository->create([
            'uuid' => bin2hex(openssl_random_pseudo_bytes(16)),
            'due' => $due,
            'tax' => $tax,
            'shipping_costs' => $shippingCosts,
            'paid' => $paid,
            'user_id' => $userId,
            'customer_id' => $customerId,
            'brand' => $brand??ConfigService::$brand,
            'shipping_address_id' => $shippingAddressId,
            'billing_address_id' => $billingAddressId,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        return $this->getById($orderId);
    }

    /** Call the method that return an array with order data from database based on order id
     * @param integer $id
     * @return array
     */
    public function getById($id)
    {
        return $this->orderRepository->getById($id);
    }

    /** Call the method that update order record if the order exist in the database
     * Return: - null if the order doesn't exist in the database
     *         - an array with the updated order
     * @param int $id
     * @param array $data
     * @return array|null
     */
    public function update($id, array $data)
    {
        $order = $this->getById($id);

        if (empty($order)) {
            return null;
        }

        $data['updated_on'] = Carbon::now()->toDateTimeString();
        $this->orderRepository->update($id, $data);

        return $this->getById($id);
    }

}