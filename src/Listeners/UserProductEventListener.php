<?php

namespace Railroad\Ecommerce\Listeners;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Usora\Entities\User;

class UserProductEventListener
{
    /**
     * @var UserProductService
     */
    protected $userProductService;

    /**
     * @param UserProductService $userProductService
     */
    public function __construct(
        UserProductService $userProductService
    ) {
        $this->userProductService = $userProductService;
    }

    public function preFlush(PreFlushEventArgs $args)
    {
        echo "\n\n UserProductEventListener::preUpdate \n\n";
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        echo "\n\n UserProductEventListener::preUpdate \n\n";

        if ($entity instanceof Subscription) {

            echo "\n\n ### \n\n";

            /**
             * @var $entity \Railroad\Ecommerce\Entities\Subscription
             */

            // echo "\n\n entity: " . var_export($entity, true) . "\n\n";

            $products = $this->getProducts($entity); // todo - finish it

            // echo "\n\n products: " . var_export($products, true) . "\n\n";

            if (
                !$entity->getCanceledOn() &&
                $entity->getIsActive() &&
                $entity->getType() == ConfigService::$typeSubscription
            ) {

                // echo "\n\n1\n\n";

                foreach ($products as $productData) {
                    // echo "\n\n2\n\n";

                    $this->userProductService->assignUserProduct(
                        $entity->getUser(),
                        $productData['product'],
                        $entity->getPaidUntil()
                    );
                }

            } else {

                $this->userProductService
                        ->removeUserProducts($entity->getUser(), $products);
            }
        }
    }

    /**
     * @param Subscription $subscription
     *
     * @return Collection
     *
     * returned collection element structure:
     * [
     *     'product' => (Product) $product,
     *     'quantity' => (int) $quantity
     * ]
     */
    public function getProducts(Subscription $subscription)
    {
        if ($subscription->getType() == ConfigService::$paymentPlanType) {

            $col = $subscription->getOrder()->getOrderItems();

            return collect($col)
                ->map(function($orderItem, $key) {
                    return [
                        'product' => $orderItem->getProduct(),
                        'quantity' => $orderItem->getQuantity()
                    ];
                });

        } else {

            return collect([
                [
                    'product' => $subscription->getProduct(),
                    'quantity' => 1
                ]
            ]);
        }
    }
}
