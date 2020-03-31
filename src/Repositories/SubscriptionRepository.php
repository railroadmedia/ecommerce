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
use Railroad\Ecommerce\Entities\User;
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
     * Gets subscriptions due to renew
     *
     * @return array
     */
    public function getSubscriptionsDueToRenew()
    {
        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->createQueryBuilder('s');

        $qb->select(['s'])
            ->where(
                $qb->expr()
                    ->eq('s.brand', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('s.canceledOn')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.stopped', ':notStopped')
            )
            ->andWhere(
                $qb->expr()
                    ->in('s.type', ':types')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->isNull('s.totalCyclesDue'),
                        $qb->expr()
                            ->eq('s.totalCyclesDue', ':zero'),
                        $qb->expr()
                            ->lt('s.totalCyclesPaid', 's.totalCyclesDue')
                    )
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->andX(
                                $qb->expr()
                                    ->eq('s.isActive', ':active'),
                                $qb->expr()
                                    ->eq('s.renewalAttempt', ':initialRenewalAttempt'),
                                $qb->expr()
                                    ->lt('s.paidUntil', ':initialRenewalDate')
                            ),
                        $qb->expr()
                            ->andX(
                                $qb->expr()
                                    ->eq('s.isActive', ':notActive'),
                                $qb->expr()
                                    ->orX(
                                        $qb->expr()
                                            ->andX(
                                                $qb->expr()
                                                    ->eq('s.renewalAttempt', ':firstRenewalAttempt'),
                                                $qb->expr()
                                                    ->lt('s.paidUntil', ':firstRenewalDate')
                                            ),
                                        $qb->expr()
                                            ->andX(
                                                $qb->expr()
                                                    ->eq('s.renewalAttempt', ':secondRenewalAttempt'),
                                                $qb->expr()
                                                    ->lt('s.paidUntil', ':secondRenewalDate')
                                            ),
                                        $qb->expr()
                                            ->andX(
                                                $qb->expr()
                                                    ->eq('s.renewalAttempt', ':thirdRenewalAttempt'),
                                                $qb->expr()
                                                    ->lt('s.paidUntil', ':thirdRenewalDate')
                                            ),
                                        $qb->expr()
                                            ->andX(
                                                $qb->expr()
                                                    ->eq('s.renewalAttempt', ':fourthRenewalAttempt'),
                                                $qb->expr()
                                                    ->lt('s.paidUntil', ':fourthRenewalDate')
                                            ),
                                        $qb->expr()
                                            ->andX(
                                                $qb->expr()
                                                    ->eq('s.renewalAttempt', ':fifthRenewalAttempt'),
                                                $qb->expr()
                                                    ->lt('s.paidUntil', ':fifthRenewalDate')
                                            )
                                    )
                            )
                    )
            )
            ->setParameter('brand', config('ecommerce.brand'))
            ->setParameter('active', true)
            ->setParameter('notStopped', false)
            ->setParameter('notActive', false)
            ->setParameter('zero', 0)
            ->setParameter(
                'types',
                [
                    Subscription::TYPE_SUBSCRIPTION,
                    Subscription::TYPE_PAYMENT_PLAN,
                ]
            )
            ->setParameter('initialRenewalAttempt', 0)
            ->setParameter('initialRenewalDate', Carbon::now())
            ->setParameter('firstRenewalAttempt', 1)
            ->setParameter(
                'firstRenewalDate',
                Carbon::now()
                    ->subHours(config('ecommerce.subscriptions_renew_cycles.first_hours'))
            )
            ->setParameter('secondRenewalAttempt', 2)
            ->setParameter(
                'secondRenewalDate',
                Carbon::now()
                    ->subDays(config('ecommerce.subscriptions_renew_cycles.second_days'))
            )
            ->setParameter('thirdRenewalAttempt', 3)
            ->setParameter(
                'thirdRenewalDate',
                Carbon::now()
                    ->subDays(config('ecommerce.subscriptions_renew_cycles.third_days'))
            )
            ->setParameter('fourthRenewalAttempt', 4)
            ->setParameter(
                'fourthRenewalDate',
                Carbon::now()
                    ->subDays(config('ecommerce.subscriptions_renew_cycles.fourth_days'))
            )
            ->setParameter('fifthRenewalAttempt', 5)
            ->setParameter(
                'fifthRenewalDate',
                Carbon::now()
                    ->subDays(config('ecommerce.subscriptions_renew_cycles.fifth_days'))
            );

        return $qb->getQuery()
                    ->getResult();
    }

    /**
     * Gets all subscriptions that the specified users have
     *
     * @param array $usersIds
     *
     * @return Subscription[]
     */
    public function getSubscriptionsForUsers(array $usersIds): array
    {
        /** @var $qb QueryBuilder */
        $qb = $this->createQueryBuilder('s');

        $qb->select(['s'])
            ->where(
                $qb->expr()
                    ->in('s.user', ':usersIds')
            );

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('usersIds', $usersIds);

        return $q->getResult();
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
                    ->lte('s.appleExpirationDate', 'CURRENT_TIMESTAMP()')
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
        if ($this->getEntityManager()
            ->getFilters()
            ->isEnabled('soft-deleteable')) {

            $this->getEntityManager()
                ->getFilters()
                ->disable('soft-deleteable');
        }

        $alias = 's';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->restrictSoftDeleted($request, $alias)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias);

        if ($request->has('user_id')) {

            $user = $this->userProvider->getUserById($request->get('user_id'));

            $qb->andWhere(
                $qb->expr()
                    ->eq($alias . '.user', ':user')
            )
                ->setParameter('user', $user);
        }

        if ($request->has('customer_id')) {
            $qb->andWhere('IDENTITY(' . $alias . '.customer) = :customerId')
                ->setParameter('customerId', $request->get('customer_id'));
        }

        $subscriptionsPage = $qb
                ->getQuery()
                ->getResult();

        $subscriptionsIds = [];

        foreach ($subscriptionsPage as $subscription) {
            $subscriptionsIds[] = $subscription->getId();
        }

        if (empty($subscriptionsIds)) {
            return new ResultsQueryBuilderComposite([], $qb);
        }

        // 1st query, made with $qb, only pulls subscriptions, to be able to paginate them correctly
        // this query may yield more results than the request limit, due to the 'one to many' relation between orders and order items, loaded by fetch join
        $decoratedQb = $this->createQueryBuilder($alias);

        $decoratedQb->orderByRequest($request, $alias)
            ->select(['s', 'p', 'o', 'pm', 'oi', 'oip'])
            ->leftJoin('s.product', 'p')
            ->leftJoin('s.order', 'o')
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'oip')
            ->leftJoin('s.paymentMethod', 'pm')
            ->where($decoratedQb->expr()->in('s.id', $subscriptionsIds));

        $results =
            $decoratedQb->getQuery()
                ->getResult();

        if (!$this->getEntityManager()
            ->getFilters()
            ->isEnabled('soft-deleteable')) {

            $this->getEntityManager()
                ->getFilters()
                ->enable('soft-deleteable');
        }

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

        $smallDateTime = Carbon::parse($smallDateTime)->startOfDay()->toDateTimeString();

        $bigDateTime =
            $request->get(
                'big_date_time',
                Carbon::now()
                    ->toDateTimeString()
            );

        $bigDateTime = Carbon::parse($bigDateTime)->endOfDay()->toDateTimeString();

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
            ->andWhere(
                $qb->expr()
                    ->isNull('s.canceledOn')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.stopped', ':not')
            )
            ->setParameter('not', false)
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
     * @param User $user
     *
     * @return Subscription[]|null
     */
    public function getUserActiveSubscription(User $user)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.user', ':user')
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
            ->setParameter('user', $user)
            ->setParameter(
                'now',
                Carbon::now()
                    ->toDateTimeString()
            );

        return $qb->getQuery()
                    ->getResult();
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
     * @param $externalAppStoreId
     * @return Subscription|null
     */
    public function getByExternalAppStoreId($externalAppStoreId)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('s')
            ->from($this->getClassName(), 's')
            ->where(
                $qb->expr()
                    ->eq('s.externalAppStoreId', ':externalAppStoreId')
            )
            ->setMaxResults(1)
            ->setParameter('externalAppStoreId', $externalAppStoreId);

        return $qb->getQuery()->getOneOrNullResult();
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

    /**
     * @param int $userId
     * @param Carbon $date
     *
     * @return Subscription[]
     */
    public function getUserMembershipSubscriptionBeforeDate(
        int $userId,
        Carbon $date
    ): array
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
                    ->lte('s.startDate', ':date')
            )
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()
                            ->eq('s.intervalType', ':intervalMonthly'),
                        $qb->expr()->orX(
                            $qb->expr()
                                ->eq('s.intervalCount', ':oneMonth'),
                            $qb->expr()
                                ->eq('s.intervalCount', ':sixMonths')
                        )
                    ),
                    $qb->expr()->andX(
                        $qb->expr()
                            ->eq('s.intervalType', ':intervalYearly'),
                        $qb->expr()
                            ->eq('s.intervalCount', ':oneYear')
                    )
                )
            )
            ->andWhere(
                $qb->expr()
                    ->in('s.type', ':membership')
            );

        $subscriptions =
            $qb->setParameter('userId', $userId)
                ->setParameter('date', $date)
                ->setParameter('intervalMonthly', config('ecommerce.interval_type_monthly'))
                ->setParameter('oneMonth', 1)
                ->setParameter('sixMonths', 6)
                ->setParameter('intervalYearly', config('ecommerce.interval_type_yearly'))
                ->setParameter('oneYear', 1)
                ->setParameter(
                    'membership',
                    [
                        Subscription::TYPE_SUBSCRIPTION,
                        Subscription::TYPE_APPLE_SUBSCRIPTION,
                        Subscription::TYPE_GOOGLE_SUBSCRIPTION
                    ]
                )
                ->getQuery()
                ->getResult();

        return $subscriptions;
    }
}
