<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;
use Railroad\Ecommerce\Requests\FailedBillingSubscriptionsRequest;
use Railroad\Ecommerce\Requests\FailedSubscriptionsRequest;

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
class SubscriptionRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * SubscriptionRepository constructor.
     *
     * @param EcommerceEntityManager $em
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        EcommerceEntityManager $em,
        UserProviderInterface $userProvider
    ) {
        parent::__construct($em, $em->getClassMetadata(Subscription::class));

        $this->userProvider = $userProvider;
    }

    /**
     * Gets subscriptions that are related to the specified products
     *
     * @return array
     */
    public function getAppleExpiredSubscriptions()
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['s', 'r'])
            ->from($this->getClassName(), 's')
            ->leftJoin('s.appleReceipt', 'r')
            ->where(
                $qb->expr()
                    ->lte('s.appleExpirationDate', ':now')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.type', ':appleSubscription')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.isActive', ':active')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.canceledOn')
            );

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('now', Carbon::now());
        $q->setParameter('appleSubscription', Subscription::TYPE_APPLE_SUBSCRIPTION);
        $q->setParameter('active', true);

        return $q->getResult();
    }

    /**
     * @param $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request): ResultsQueryBuilderComposite
    {
        $alias = 's';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->restrictSoftDeleted($request, $alias)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias)
            ->select(['s', 'p', 'o', 'pm', 'oi', 'oip'])
            ->leftJoin('s.product', 'p')
            ->leftJoin('s.order', 'o')
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'oip')
            ->leftJoin('s.paymentMethod', 'pm');

        if ($request->has('user_id')) {

            $user = $this->userProvider->getUserById($request->get('user_id'));

            $qb->andWhere(
                $qb->expr()
                    ->eq('s' . '.user', ':user')
            )
                ->setParameter('user', $user);
        }

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    /**
     * @param $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexFailedByRequest(FailedSubscriptionsRequest $request): ResultsQueryBuilderComposite
    {
        $smallDateTime =
            $request->get(
                'small_date_time',
                Carbon::now()
                    ->subDay()
                    ->toDateTimeString()
            );

        $bigDateTime =
            $request->get(
                'big_date_time',
                Carbon::now()
                    ->toDateTimeString()
            );

        $alias = 's';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias)
            ->select(['s', 'p', 'o', 'pm'])
            ->leftJoin('s.product', 'p')
            ->leftJoin('s.order', 'o')
            ->leftJoin('s.paymentMethod', 'pm')
            ->andWhere(
                $qb->expr()
                    ->isNull('s.deletedAt')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.type', ':type')
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull(
                            's.canceledOn'
                        ),
                        $qb->expr()->gt('s.canceledOn', ':canceledSmallDateTime'),
                        $qb->expr()->lte('s.canceledOn', ':canceledBigDateTime')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull(
                            's.canceledOn'
                        ),
                        $qb->expr()->eq('s.isActive', ':activity'),
                        $qb->expr()->gt('s.paidUntil', ':paidSmallDateTime'),
                        $qb->expr()->lte('s.paidUntil', ':paidBigDateTime')
                    )
                )
            )
            ->setParameter('canceledSmallDateTime', $smallDateTime)
            ->setParameter('canceledBigDateTime', $bigDateTime)
            ->setParameter('activity', false)
            ->setParameter('paidSmallDateTime', $smallDateTime)
            ->setParameter('paidBigDateTime', $bigDateTime)
            ->setParameter('type', $request->get('type'));

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    /**
     * @param $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexFailedBillingByRequest(FailedBillingSubscriptionsRequest $request): ResultsQueryBuilderComposite
    {
        $smallDateTime =
            $request->get(
                'small_date_time',
                Carbon::now()
                    ->subDays(14)
                    ->toDateTimeString()
            );

        $bigDateTime =
            $request->get(
                'big_date_time',
                Carbon::now()
                    ->toDateTimeString()
            );

        $qb = $this->createQueryBuilder('s');

        $qb->paginateByRequest($request)
            ->orderByRequest($request, 's')
            ->restrictBrandsByRequest($request, 's')
            ->select(['s', 'pr', 'o', 'pm', 'p'])
            ->leftJoin('s.failedPayment', 'p')
            ->leftJoin('s.product', 'pr')
            ->leftJoin('s.order', 'o')
            ->leftJoin('s.paymentMethod', 'pm')
            ->andWhere('p.status = :failed')
            ->andWhere(
                $qb->expr()
                    ->gt('p.createdAt', ':smallDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->lte('p.createdAt', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.type', ':type')
            )
            ->setParameter('failed', 'failed')
            ->setParameter('smallDateTime', $smallDateTime)
            ->setParameter('bigDateTime', $bigDateTime)
            ->setParameter('type', $request->get('type'));

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
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
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->in('s.product', ':products')
            );

        /** @var $q Query */
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
     * @return Subscription|null
     *
     * @throws NonUniqueResultException
     */
    public function getOrderProductSubscription(
        Order $order,
        Product $product
    ): ?Subscription
    {
        /** @var $qb QueryBuilder */
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

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('order', $order)
            ->setParameter('product', $product);

        return $q->getOneOrNullResult();
    }

    /**
     * @param $userId
     * @param array $productIds
     * @param bool $activeOnly - default false
     *
     * @return Subscription|null
     */
    public function getUserSubscriptionForProducts($userId, array $productIds, $activeOnly = false)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.user', ':userId')
            )
            ->andWhere(
                $qb->expr()
                    ->in('IDENTITY(s.product)', ':productIds')
            );

        if ($activeOnly) {
            $qb->andWhere('s.isActive = true')
                ->andWhere(
                    $qb->expr()
                        ->gt('s.paidUntil', ':now')
                )
                ->andWhere(
                    $qb->expr()
                        ->isNull('s.canceledOn')
                )
                ->setParameter(
                    'now',
                    Carbon::now()
                        ->toDateTimeString()
                );
        }

        $subscriptions =
            $qb->setParameter('userId', $userId)
                ->setParameter('productIds', $productIds)
                ->orderBy('s.createdAt', 'desc')
                ->getQuery()
                ->getResult();

        if (count($subscriptions) > 1 && $activeOnly) {
            error_log(
                'User ' .
                $userId .
                ' has more than 1 active subscription for the given products ' .
                implode(',', $productIds)
            );
        }

        return $subscriptions[0] ?? null;
    }

    /**
     * @param $userId
     * @param array $limitToProductIds
     * @return Subscription[]
     */
    public function getAllUsersSubscriptions($userId, array $limitToProductIds = [])
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.user', ':userId')
            );

        if (!empty($limitToProductIds)) {
            $qb->andWhere(
                $qb->expr()
                    ->in('IDENTITY(s.product)', ':productIds')
            )
                ->setParameter('productIds', $limitToProductIds);
        }

        $subscriptions =
            $qb->setParameter('userId', $userId)
                ->orderBy('s.createdAt', 'desc')
                ->getQuery()
                ->getResult();

        return $subscriptions;
    }

    /**
     * @param Order $order
     *
     * @return Subscription[]
     */
    public function getOrderSubscriptions(Order $order): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.order', ':order')
            )
            ->setParameter('order', $order);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Order $order
     *
     * @return Subscription[]
     */
    public function getPaymentMethodSubscriptions(PaymentMethod $paymentMethod): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.paymentMethod', ':paymentMethod')
            )
            ->setParameter('paymentMethod', $paymentMethod);

        return $qb->getQuery()->getResult();
    }
}
