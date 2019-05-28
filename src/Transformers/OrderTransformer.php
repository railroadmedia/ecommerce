<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Order;

class OrderTransformer extends TransformerAbstract
{
    protected $defaultIncludes = ['orderItem', 'user', 'customer', 'billingAddress', 'shippingAddress'];

    public function transform(Order $order)
    {
        return [
            'id' => $order->getId(),
            'total_due' => $order->getTotalDue(),
            'product_due' => $order->getProductDue(),
            'taxes_due' => $order->getTaxesDue(),
            'shipping_due' => $order->getShippingDue(),
            'finance_due' => $order->getFinanceDue(),
            'total_paid' => $order->getTotalPaid(),
            'brand' => $order->getBrand(),
            'note' => $order->getNote(),
            'deleted_at' => $order->getDeletedOn() ?
                $order->getDeletedOn()
                    ->toDateTimeString() : null,
            'created_at' => $order->getCreatedAt() ?
                $order->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $order->getUpdatedAt() ?
                $order->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    public function includeOrderItem(Order $order)
    {
        if (empty($order->getOrderItems()) || $order->getOrderItems()->count() == 0) {
            return null;
        }

        if ($order->getOrderItems()
                ->first() instanceof Proxy) {
            return $this->collection(
                $order->getOrderItems(),
                new EntityReferenceTransformer(),
                'orderItem'
            );
        }
        else {
            return $this->collection(
                $order->getOrderItems(),
                new OrderItemTransformer(),
                'orderItem'
            );
        }
    }

    public function includeUser(Order $order)
    {
        if (empty($order->getUser())) {
            return null;
        }

        $userProvider = app()->make(UserProviderInterface::class);

        $userTransformer = $userProvider->getUserTransformer();

        return $this->item(
            $order->getUser(),
            $userTransformer,
            'user'
        );
    }

    public function includeCustomer(Order $order)
    {
        if (empty($order->getCustomer())) {
            return null;
        }

        if ($order->getCustomer() instanceof Proxy) {
            return $this->item(
                $order->getCustomer(),
                new EntityReferenceTransformer(),
                'customer'
            );
        }
        else {
            return $this->item(
                $order->getCustomer(),
                new CustomerTransformer(),
                'customer'
            );
        }
    }

    public function includeBillingAddress(Order $order)
    {
        if (empty($order->getBillingAddress())) {
            return null;
        }

        if ($order->getBillingAddress() instanceof Proxy) {
            return $this->item(
                $order->getBillingAddress(),
                new EntityReferenceTransformer(),
                'address'
            );
        }
        elseif (!empty($order->getBillingAddress())) {
            return $this->item(
                $order->getBillingAddress(),
                new AddressTransformer(),
                'address'
            );
        }

        return null;
    }

    public function includeShippingAddress(Order $order)
    {
        if (empty($order->getShippingAddress())) {
            return null;
        }

        if ($order->getShippingAddress() instanceof Proxy) {
            return $this->item(
                $order->getShippingAddress(),
                new EntityReferenceTransformer(),
                'address'
            );
        }
        elseif (!empty($order->getShippingAddress())) {
            return $this->item(
                $order->getShippingAddress(),
                new AddressTransformer(),
                'address'
            );
        }

        return null;
    }
}
