<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use DateTimeInterface;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Events\UserProducts\UserProductCreated;
use Railroad\Ecommerce\Events\UserProducts\UserProductDeleted;
use Railroad\Ecommerce\Events\UserProducts\UserProductUpdated;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
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
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ArrayCache
     */
    public $arrayCache;

    /**
     * UserProductService constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param UserProductRepository $userProductRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        UserProductRepository $userProductRepository,
        ProductRepository $productRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userProductRepository = $userProductRepository;
        $this->productRepository = $productRepository;
        $this->arrayCache = app()->make('EcommerceArrayCache');
    }

    /**
     * @param $userId
     * @param $productId
     *
     * @return bool
     *
     * @throws ORMException
     */
    public function hasProduct($userId, $productId)
    {
        $userProducts = $this->getAllUsersProducts($userId);

        foreach ($userProducts as $userProduct) {
            if ($userProduct->getProduct()
                    ->getId() == $productId &&
                ($userProduct->isValid())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $userId
     * @param array $productIds
     *
     * @return bool
     *
     * @throws ORMException
     */
    public function hasAnyOfProducts($userId, array $productIds)
    {
        $userProducts = $this->getAllUsersProducts($userId);

        foreach ($userProducts as $userProduct) {
            if (in_array(
                    $userProduct->getProduct()
                        ->getId(),
                    $productIds
                ) &&
                ($userProduct->isValid())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $userId
     * @param $productId
     *
     * @return bool|Carbon|null
     *
     * @throws ORMException
     */
    public function getProductExpirationDate($userId, $productId)
    {
        $userProducts = $this->getAllUsersProducts($userId);

        foreach ($userProducts as $userProduct) {
            if ($userProduct->getProduct()
                    ->getId() == $productId) {
                if ($userProduct->getExpirationDate() == null) {
                    return null;
                }

                return Carbon::parse($userProduct->getExpirationDate());
            }
        }

        return false;
    }

    /**
     * @param int $userId
     *
     * @return UserProduct[]
     *
     * @throws ORMException
     */
    public function getAllUsersProducts(int $userId)
    {
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb->where(
            $qb->expr()
                ->eq('up.user', ':userId')
        )
            ->setParameter('userId', $userId);

        return $qb->getQuery()
            ->setResultCacheDriver($this->arrayCache)
            ->setQueryCacheDriver($this->arrayCache)
            ->getResult();
    }

    /**
     * @param array $userIds
     * @return UserProduct[]
     *
     * @throws ORMException
     */
    public function getManyUsersProducts(array $userIds)
    {
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb->where(
            $qb->expr()
                ->in('up.user', ':userIds')
        )
            ->setParameter('userIds', $userIds);

        return $qb->getQuery()
            ->setResultCacheDriver($this->arrayCache)
            ->setQueryCacheDriver($this->arrayCache)
            ->getResult();
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
        /** @var $qb QueryBuilder */
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb->where(
            $qb->expr()
                ->eq('up.user', ':user')
        )
            ->andWhere(
                $qb->expr()
                    ->eq('up.product', ':product')
            )
            ->setParameter('user', $user)
            ->setParameter('product', $product);

        return $qb->getQuery()
            ->getResult()[0] ?? null;
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
        /** @var $qb QueryBuilder */
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb->where(
            $qb->expr()
                ->eq('up.user', ':user')
        )
            ->andWhere(
                $qb->expr()
                    ->in('up.product', ':products')
            )
            ->setParameter('user', $user)
            ->setParameter('products', $products);

        $collection =
            $qb->getQuery()
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

        $userProduct->setUser($user);
        $userProduct->setProduct($product);
        $userProduct->setExpirationDate($expirationDate);
        $userProduct->setQuantity($quantity);

        $this->entityManager->persist($userProduct);
        $this->entityManager->flush();

        event(new UserProductCreated($userProduct));

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
        $oldUserProduct = clone($userProduct);

        $userProduct->setExpirationDate($expirationDate);
        $userProduct->setQuantity($quantity);
        $userProduct->setUpdatedAt(Carbon::now());

        $this->entityManager->persist($userProduct);
        $this->entityManager->flush();

        event(new UserProductUpdated($userProduct, $oldUserProduct));

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

        event(new UserProductDeleted($userProduct));
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
        $quantity = 0,
        $allowPackBonusMembershipAccess = true
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

        if ($allowPackBonusMembershipAccess) {
            $this->handlePackBonusMembershipAccess($product, $user);
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
        $userProducts = $this->getUserProducts(
            $user,
            $products->pluck('product')
                ->all()
        );

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

                event(new UserProductDeleted($userProduct));
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
        if ($subscription->getType() == config('ecommerce.type_payment_plan')) {
            if (!$subscription->getOrder()) {
                return collect([]);
            }

            return collect(
                $subscription->getOrder()
                    ->getOrderItems()
            )->map(
                function ($orderItem) {
                    /** @var $orderItem OrderItem */
                    return [
                        'product' => $orderItem->getProduct(),
                        'quantity' => $orderItem->getQuantity(),
                    ];
                }
            );
        } else {
            return collect(
                [
                    [
                        'product' => $subscription->getProduct(),
                        'quantity' => 1,
                    ],
                ]
            );
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

        // we only want to update the expiration date of non-payment plan subscription products
        if ($subscription->getType() != Subscription::TYPE_PAYMENT_PLAN) {
            foreach ($products as $productData) {
                /** @var $paidUntil Carbon */
                $paidUntil = $subscription->getPaidUntil()
                    ->copy();

                $this->assignUserProduct(
                    $subscription->getUser(),
                    $productData['product'],
                    $paidUntil->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                );
            }
        }
    }

    /**
     * Updates subscription user products
     *
     * @param Subscription $subscription
     *
     * @throws Throwable
     */
    public function updateSubscriptionProductsApp(Subscription $subscription)
    {
        $products = $this->getSubscriptionProducts($subscription);

        // we only want to update the expiration date of non-payment plan subscription products
        if ($subscription->getType() != Subscription::TYPE_PAYMENT_PLAN) {
            foreach ($products as $productData) {
                /** @var $paidUntil Carbon */
                $paidUntil = $subscription->getPaidUntil()
                    ->copy();

                $this->assignUserProduct(
                    $subscription->getUser(),
                    $productData['product'],
                    $paidUntil->addDays(
                        config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 5)
                    )
                );
            }
        }
    }

    /**
     * If the user has or had any digital product from brand, return true. Otherwise, false.
     *
     * @param User $user
     * @param $brand
     * @throws Throwable
     */
    public function userHadOrHasAnyDigitalProductsForBrand(User $user, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb->select('count(up.id)')
            ->leftJoin('up.product', 'p')
            ->where($qb->expr()->eq('up.user', ':user'))
            ->andWhere($qb->expr()->eq('p.brand', ':brand'))
            ->andWhere($qb->expr()->in('p.type', ":types"))
            ->setParameter('user', $user)
            ->setParameter('types', [Product::TYPE_DIGITAL_ONE_TIME, Product::TYPE_DIGITAL_SUBSCRIPTION])
            ->setParameter('brand', $brand);

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getLatestExpirationDateByBrand(User $user, string $brand)
    {
        return $this->userProductRepository->getLatestExpirationDateByBrand($user, $brand);
    }

    public function handlePackBonusMembershipAccess(Product $product, User $user): void
    {
        $membershipAccessExpirationDate = $product->getDigitalMembershipAccessExpirationDate();
        if (empty($membershipAccessExpirationDate) || $membershipAccessExpirationDate <= $user->getMembershipExpirationDate()) {
            return;
        }

        //Handle adding a fixed membership access time for cohort packs
        $membershipProduct = $this->productRepository->bySku('musora-access-pack-bonus');
        if (!$membershipProduct) {
            Log::error("Cohort pack sku does not exist.");
            return;
        }
        $membershipProduct = $this->assignUserProduct(
            $user,
            $membershipProduct,
            $membershipAccessExpirationDate,
            1,
            false //avoid infinite looping
        );
    }
}