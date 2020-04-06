<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
     *
     * @throws ORMException
     * @throws OptimisticLockException
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

                $orderItemFulfillment->setOrder($order);
                $orderItemFulfillment->setOrderItem($orderItem);
                $orderItemFulfillment->setStatus(config('ecommerce.fulfillment_status_pending'));
                $orderItemFulfillment->setCreatedAt(Carbon::now());

                $this->entityManager->persist($orderItemFulfillment);

                $persisted = true;
            }
        }

        if ($persisted) {
            $this->entityManager->flush();
        }
    }
}
