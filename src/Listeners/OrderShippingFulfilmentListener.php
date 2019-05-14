<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

class OrderShippingFulfilmentListener
{
    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

    /**
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param OrderEvent $event
     */
    public function handle(OrderEvent $event)
    {
        $order = $event->getOrder();

        $orderItems = $order->getOrderItems();

        $persisted = false;

        foreach ($orderItems as $orderItem) {

            if ($orderItem->getProduct()
                ->getIsPhysical()) {

                $orderItemFulfillment = new OrderItemFulfillment();

                $orderItemFulfillment->setOrder($order)
                    ->setOrderItem($orderItem)
                    ->setStatus(config('ecommerce.fulfillment_status_pending'))
                    ->setCreatedAt(Carbon::now());

                $this->entityManager->persist($orderItemFulfillment);

                $persisted = true;
            }
        }

        if ($persisted) {
            $this->entityManager->flush();
        }
    }
}
