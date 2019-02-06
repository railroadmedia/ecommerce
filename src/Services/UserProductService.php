<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Usora\Entities\User;

class UserProductService
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    protected $userProductRepository;

    /**
     * UserProductService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        $this->userProductRepository = $this->entityManager
                ->getRepository(UserProduct::class);
    }

    /**
     * Get user product based on user id and product id.
     *
     * @param User $user
     * @param Product $product
     *
     * @return UserProduct|null
     */
    public function getUserProduct(
        User $user,
        Product $product
    ): ?UserProduct {

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb
            ->where($qb->expr()->eq('up.user', ':user'))
            ->andWhere($qb->expr()->eq('up.product', ':product'))
            ->setParameter('user', $user)
            ->setParameter('product', $product);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Create new record in user_product table.
     *
     * @param User $user
     * @param Product $product
     * @param DateTimeInterface $expirationDate
     * @param int $quantity
     *
     * @return UserProduct
     */
    public function createUserProduct(
        User $user,
        Product $product,
        \DateTimeInterface $expirationDate,
        $quantity
    ): UserProduct {

        $userProduct = new UserProduct();

        $userProduct
            ->setUser($user)
            ->setProduct($product)
            ->setExpirationDate($expirationDate)
            ->setQuantity($quantity);

        // tmp

        // $this->entityManager->persist($userProduct);
        // $this->entityManager->flush();

        return $userProduct;
    }

    /**
     * Update user product: quantity, expiration date
     *
     * @param UserProduct $userProduct
     * @param DateTimeInterface $expirationDate
     * @param int $quantity
     *
     * @return UserProduct
     */
    public function updateUserProduct(
        UserProduct $userProduct,
        \DateTimeInterface $expirationDate,
        $quantity
    ) {
        $userProduct
            ->setExpirationDate($expirationDate)
            ->setQuantity($quantity)
            ->setUpdatedAt(Carbon::now());

        $this->entityManager->persist($userProduct);
        $this->entityManager->flush();

        return $userProduct;
    }

    /**
     * Delete user product
     *
     * @param UserProduct $userProduct
     */
    public function deleteUserProduct(UserProduct $userProduct)
    {
        $this->entityManager->remove($userProduct);
        $this->entityManager->flush();
    }

    /**
     * Assign new products to user or update product's quantity.
     *
     * @param User $user
     * @param Product $product
     * @param DateTimeInterface $expirationDate
     * @param int $quantity
     */
    public function assignUserProduct(
        User $user,
        Product $product,
        \DateTimeInterface $expirationDate,
        $quantity = 0
    ) {

        /**
         * @var $userProduct UserProduct
         */
        $userProduct = $this->getUserProduct($user, $product);

        if (!$userProduct) {
            $productQuantity = ($quantity == 0) ? 1 : $quantity;
            $this->createUserProduct(
                $user,
                $product,
                $expirationDate,
                $productQuantity
            );
        } else {
            $this->updateUserProduct(
                $userProduct,
                $expirationDate,
                ($userProduct->getQuantity() + $quantity)
            );
        }
    }

    /**
     * Remove user products.
     *
     * @param User $user
     * @param array $products - collection of elements:
     * [
     *     'product' => (Product) $product,
     *     'quantity' => (int) $quantity
     * ]
     */
    public function removeUserProducts(
        User $user,
        $products
    ) {
        foreach ($products as $productData) {

            /**
             * @var $userProduct UserProduct
             */
            $userProduct = $this->getUserProduct(
                $user,
                $productData['product']
            );

            if (!$userProduct) {
                continue;
            }

            if (
                ($userProduct->getQuantity() == 1) ||
                ($userProduct->getQuantity() - $productData['quantity'] <= 0)
            ) {
                $this->deleteUserProduct($userProduct);
            } else {
                $this->updateUserProduct(
                    $userProduct,
                    $userProduct->getExpirationDate(),
                    $userProduct->getQuantity() - $productData['quantity']
                );
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
    public function getSubscriptionProducts(Subscription $subscription)
    {
        if ($subscription->getType() == ConfigService::$paymentPlanType) {

            if (!$subscription->getOrder()) {
                return collect([]);
            }

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

    public function updateSubscriptionProducts(Subscription $subscription)
    {
        $products = $this->getSubscriptionProducts($subscription);

        if ($subscription->getIsActive()) {

            foreach ($products as $productData) {

                // tmp

                // $this->assignUserProduct(
                //     $subscription->getUser(),
                //     $productData['product'],
                //     $subscription->getPaidUntil()
                // );
            }
        } else {
            $this->removeUserProducts(
                $subscription->getUser(),
                $products
            );
        }
    }
}