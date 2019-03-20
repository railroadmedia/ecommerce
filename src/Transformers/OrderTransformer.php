<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Contracts\UserProviderInterface;

class OrderTransformer extends TransformerAbstract
{
    public function transform(Order $order)
    {
        if (count($order->getOrderItems())) {
            $this->defaultIncludes[] = 'orderItem';
        }

        if ($order->getUser()) {
            // user relation is nullable
            $this->defaultIncludes[] = 'user';
        }

        if ($order->getCustomer()) {
            // customer relation is nullable
            $this->defaultIncludes[] = 'customer';
        }

        if ($order->getBillingAddress()) {
            $this->defaultIncludes[] = 'billingAddress';
        }

        if ($order->getShippingAddress()) {
            $this->defaultIncludes[] = 'shippingAddress';
        }

        return [
            'id' => $order->getId(),
            'total_due' => $order->getTotalDue(),
            'product_due' => $order->getProductDue(),
            'taxes_due' => $order->getTaxesDue(),
            'shipping_due' => $order->getShippingDue(),
            'finance_due' => $order->getFinanceDue(),
            'total_paid' => $order->getTotalPaid(),
            'brand' => $order->getBrand(),
            'deleted_at' => $order->getDeletedOn() ? $order->getDeletedOn()->toDateTimeString() : null,
            'created_at' => $order->getCreatedAt() ? $order->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $order->getUpdatedAt() ? $order->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeOrderItem(Order $order)
    {
        if ($order->getOrderItems()->first() instanceof Proxy) {
            return $this->collection(
                $order->getOrderItems(),
                new EntityReferenceTransformer(),
                'orderItem'
            );
        } else {
            return $this->collection(
                $order->getOrderItems(),
                new OrderItemTransformer(),
                'orderItem'
            );
        }
    }

    public function includeUser(Order $order)
    {
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
        if ($order->getCustomer() instanceof Proxy) {
            return $this->item(
                $order->getCustomer(),
                new EntityReferenceTransformer(),
                'customer'
            );
        } else {
            return $this->item(
                $order->getCustomer(),
                new CustomerTransformer(),
                'customer'
            );
        }
    }

    public function includeBillingAddress(Order $order)
    {
        if ($order->getBillingAddress() instanceof Proxy) {
            return $this->item(
                $order->getBillingAddress(),
                new EntityReferenceTransformer(),
                'address'
            );
        } else {
            return $this->item(
                $order->getBillingAddress(),
                new AddressTransformer(),
                'address'
            );
        }
    }

    public function includeShippingAddress(Order $order)
    {
        if ($order->getShippingAddress() instanceof Proxy) {
            return $this->item(
                $order->getShippingAddress(),
                new EntityReferenceTransformer(),
                'address'
            );
        } else {
            return $this->item(
                $order->getShippingAddress(),
                new AddressTransformer(),
                'address'
            );
        }
    }
}
