<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Order;

class OrderTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = ['orderItem', 'user', 'customer', 'billingAddress', 'shippingAddress'];

    /**
     * @param Order $order
     *
     * @return array
     */
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
            'deleted_at' => $order->getDeletedAt() ?
                $order->getDeletedAt()
                    ->toDateTimeString() : null,
            'created_at' => $order->getCreatedAt() ?
                $order->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $order->getUpdatedAt() ?
                $order->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    /**
     * @param Order $order
     *
     * @return Collection|null
     */
    public function includeOrderItem(Order $order)
    {
        if (empty($order->getOrderItems()) || $order->getOrderItems()->count() == 0) {
            return null;
        }

        $transformer = new OrderItemTransformer();
        $defaultIncludes = $transformer->getDefaultIncludes();
        $transformer->setDefaultIncludes(array_diff($defaultIncludes, ['order']));

        return $this->collection(
            $order->getOrderItems(),
            $transformer,
            'orderItem'
        );
    }

    /**
     * @param Order $order
     *
     * @return Item|null
     */
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

    /**
     * @param Order $order
     *
     * @return Item|null
     */
    public function includeCustomer(Order $order)
    {
        if (empty($order->getCustomer())) {
            return null;
        }

        if ($order->getCustomer() instanceof Proxy && !$order->getCustomer()->__isInitialized()) {
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

    /**
     * @param Order $order
     *
     * @return Item|null
     */
    public function includeBillingAddress(Order $order)
    {
        if (empty($order->getBillingAddress())) {
            return null;
        }

        return $this->item(
            $order->getBillingAddress(),
            new AddressTransformer(),
            'address'
        );
    }

    /**
     * @param Order $order
     *
     * @return Item|null
     */
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
