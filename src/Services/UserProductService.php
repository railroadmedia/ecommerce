<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use DateTimeInterface;
use Doctrine\ORM\EntityRepository;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Throwable;

class UserProductService
{
    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    protected $userProductRepository;

    /**
     * UserProductService constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param UserProductRepository $userProductRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        UserProductRepository $userProductRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userProductRepository = $userProductRepository;
    }

    /**
     * Get user product based on user and product.
     *
     * @param User $user
     * @param Product $product
     *
     * @return UserProduct|null
     *
     * @throws Throwable
     */
    public function getUserProduct(
        User $user,
        Product $product
    ): ?UserProduct {

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb->where($qb->expr()
                ->eq('up.user', ':user'))
            ->andWhere($qb->expr()
                ->eq('up.product', ':product'))
            ->setParameter('user', $user)
            ->setParameter('product', $product);

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get user products collection based on user and product array
     *
     * @param User $user
     * @param array $products
     *
     * @return array
     */
    public function getUserProducts(
        User $user,
        $products
    ): array {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb->where($qb->expr()
                ->eq('up.user', ':user'))
            ->andWhere($qb->expr()
                ->in('up.product', ':products'))
            ->setParameter('user', $user)
            ->setParameter('products', $products);

        $collection = $qb->getQuery()
            ->getResult();

        $map = [];

        foreach ($collection as $userProduct) {
            /**
             * @var $userProduct UserProduct
             */
            $map[$userProduct->getId()] = $userProduct;
        }

        return $map;
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
     *
     * @throws Throwable
     */
    public function createUserProduct(
        User $user,
        Product $product,
        ?DateTimeInterface $expirationDate,
        $quantity
    ): UserProduct {

        $userProduct = new UserProduct();

        $userProduct->setUser($user)
            ->setProduct($product)
            ->setExpirationDate($expirationDate)
            ->setQuantity($quantity);

        $this->entityManager->persist($userProduct);
        $this->entityManager->flush();

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
     *
     * @throws Throwable
     */
    public function updateUserProduct(
        UserProduct $userProduct,
        ?DateTimeInterface $expirationDate,
        $quantity
    ) {
        $userProduct->setExpirationDate($expirationDate)
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
     *
     * @throws Throwable
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
     *
     * @throws Throwable
     */
    public function assignUserProduct(
        User $user,
        Product $product,
        ?DateTimeInterface $expirationDate,
        $quantity = 0
    ) {

        /**
         * @var $userProduct UserProduct
         */
        $userProduct = $this->getUserProduct($user, $product);

        if (!$userProduct) {
            $productQuantity = ($quantity == 0) ? 1 : $quantity;
            $this->createUserProduct($user, $product, $expirationDate, $productQuantity);
        } else {
            $this->updateUserProduct($userProduct, $expirationDate, ($userProduct->getQuantity() + $quantity));
        }
    }

    /**
     * Remove user products.
     *
     * @param User $user
     * @param Collection $products - collection of elements:
     * [
     *     'product' => (Product) $product,
     *     'quantity' => (int) $quantity
     * ]
     *
     * @throws Throwable
     */
    public function removeUserProducts(
        User $user,
        $products
    ) {
        $userProducts = $this->getUserProducts($user, $products->pluck('product')
            ->all());

        foreach ($products as $productData) {

            /**
             * @var $product Product
             */
            $product = $productData['product'];

            if (!$userProduct = $userProducts[$product->getId()] ?? null) {
                continue;
            }

            /**
             * @var $userProduct UserProduct
             */

            if (($userProduct->getQuantity() == 1) || ($userProduct->getQuantity() - $productData['quantity'] <= 0)) {
                $this->entityManager->remove($userProduct);
            } else {

                $quantity = $userProduct->getQuantity() - $productData['quantity'];

                $userProduct->setQuantity($quantity);
            }
        }

        $this->entityManager->flush();
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

            return collect($subscription->getOrder()
                ->getOrderItems())->map(function ($orderItem) {
                    /**
                     * @var $orderItem \Railroad\Ecommerce\Entities\OrderItem
                     */
                    return [
                        'product' => $orderItem->getProduct(),
                        'quantity' => $orderItem->getQuantity(),
                    ];
                });

        } else {

            return collect([
                [
                    'product' => $subscription->getProduct(),
                    'quantity' => 1,
                ],
            ]);
        }
    }

    /**
     * Updates subscription user products
     *
     * @param Subscription $subscription
     *
     * @throws Throwable
     */
    public function updateSubscriptionProducts(Subscription $subscription)
    {
        $products = $this->getSubscriptionProducts($subscription);

        if ($subscription->getIsActive()) {

            foreach ($products as $productData) {

                $this->assignUserProduct($subscription->getUser(), $productData['product'],
                    $subscription->getPaidUntil());
            }
        } else {
            $this->removeUserProducts($subscription->getUser(), $products);
        }
    }
}