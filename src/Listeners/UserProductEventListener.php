<?php

namespace Railroad\Ecommerce\Listeners;

use Illuminate\Support\Collection;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
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

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        if ($entity instanceof Subscription) {

            /**
             * @var $entity \Railroad\Ecommerce\Entities\Subscription
             */

            $products = $this->getProducts($entity); // todo - finish it

            if (
                !$entity->getCanceledOn() &&
                $entity->getIsActive() &&
                $entity->getType() == ConfigService::$typeSubscription
            ) {

                foreach ($products as $product) {
                    $this->assignUserProduct(
                        $entity->getUser(),
                        $product['product'],
                        $entity->getPaidUntil()
                    );
                }

            } else {
                $this->removeUserProducts(
                    $entity->getUser(),
                    $products
                );
            }
        }
    }

    /**
     * @param User $user
     * @param Collection $products - returned by UserProductEventListener::getProducts
     */
    private function removeUserProducts(User $user, Collection $products)
    {
        $productsMap = array_pluck($products, 'quantity', 'product_id');

        foreach ($products as $productData) {

            /**
             * @var $product \Railroad\Ecommerce\Entities\Product
             */
            $product = $element['product'];

            $quantity = $element['quantity'];

            $productsMap[$product->getId()] = $quantity;
        }

        // todo - review service logic and refactor to use entities and entities collections instead of ids and ids map
        $this->userProductService
            ->removeUserProducts($user->getId(), $productsMap);
    }

    /**
     * @param User $user
     * @param Product $product
     * @param DateTimeInterface $expirationDate
     * @param int $quantity
     */
    private function assignUserProduct(
        User $user,
        Product $product,
        \DateTimeInterface $expirationDate,
        int $quantity = 0
    ) {
        // todo - review service logic and refactor to use entities instead of ids
        $this->userProductService->assignUserProduct(
            $user->getId(),
            $product->getId(),
            $expirationDate,
            $quantity
        );
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

            return collect($subscription->getOrder()->getOrderItems())
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
