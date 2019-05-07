<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class SubscriptionRepository
 *
 * @method Subscription find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Subscription[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class SubscriptionRepository extends EntityRepository
{
    /**
     * SubscriptionRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Subscription::class));
    }

    /**
     * Gets subscriptions that are related to the specified products
     *
     * @param array $products - array of product entities
     *
     * @return array
     */
    public function getProductsSubscriptions(array $products): array
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->in('s.product', ':products')
            );

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('products', $products);

        return $q->getResult();
    }

    /**
     * Gets an order's subscription, based on specified product
     *
     * @param Order $order
     * @param Product $product
     *
     * @return array
     */
    public function getOrderProductSubscription(
        Order $order,
        Product $product
    ): ?Subscription
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.order', ':order')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.product', ':product')
            );

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('order', $order)
            ->setParameter('product', $product);

        return $q->getOneOrNullResult();
    }

    /**
     * @param $userId
     * @param array $productIds
     * @return Subscription|null
     */
    public function getActiveUserSubscriptionForProducts($userId, array $productIds)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $subscriptions =
            $qb->select('s')
                ->from($this->getClassName(), 's')
                ->where(
                    $qb->expr()
                        ->eq('s.user', ':userId')
                )
                ->andWhere(
                    $qb->expr()
                        ->in('IDENTITY(s.product)', ':productIds')
                )
                ->andWhere('s.isActive = true')
                ->andWhere(
                    $qb->expr()
                        ->gt('s.paidUntil', ':now')
                )
                ->andWhere(
                    $qb->expr()
                        ->isNull('s.canceledOn')
                )
                ->setParameter('userId', $userId)
                ->setParameter('productIds', $productIds)
                ->setParameter(
                    'now',
                    Carbon::now()
                        ->toDateTimeString()
                )
                ->getQuery()
                ->getResult();

        if (count($subscriptions) > 1) {
            error_log(
                'User ' .
                $userId .
                ' has more than 1 active subscription for the given products ' .
                implode(',', $productIds)
            );
        }
        
        return $subscriptions[0] ?? null;
    }
}
