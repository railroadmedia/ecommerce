<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\QueryBuilders\FromRequestEcommerceQueryBuilder;
use Railroad\Ecommerce\Repositories\Traits\UseFormRequestQueryBuilder;

/**
 * Class PaymentRepository
 *
 * @method Payment findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Payment[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class PaymentRepository extends RepositoryBase
{
    use UseFormRequestQueryBuilder;

    /**
     * PaymentRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Payment::class));
    }

    /**
     * @param int $id
     * @return Payment
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function find(int $id)
    {
        $alias = 'p';

        /** @var $qb FromRequestEcommerceQueryBuilder */
        $qb = $this->createQueryBuilder($alias);

        $qb->select(['p', 'pm', 'cc', 'ppba', 'op', 'sp', 'o', 's'])
            ->leftJoin('p.paymentMethod', 'pm')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba')
            ->leftJoin('p.orderPayment', 'op')
            ->leftJoin('p.subscriptionPayment', 'sp')
            ->leftJoin('op.order', 'o')
            ->leftJoin('sp.subscription', 's')
            ->where('p.id = :paymentId')
            ->setParameter('paymentId', $id);

        return $qb->getQuery()
            ->getSingleResult();
    }

    /**
     * @param $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request): ResultsQueryBuilderComposite
    {
        $alias = 'p';

        /** @var $qb FromRequestEcommerceQueryBuilder */
        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->restrictSoftDeleted($request, $alias)
            ->orderByRequest($request, $alias)
            ->select(['p', 'pm', 'cc', 'ppba'])
            ->leftJoin('p.paymentMethod', 'pm')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba');

        if (!empty($request->get('order_id'))) {
            $aliasOrderPayment = 'op';
            $qb->join($alias . '.orderPayment', $aliasOrderPayment)
                ->where(
                    $qb->expr()
                        ->eq(
                            'IDENTITY(' . $aliasOrderPayment . '.order)',
                            ':orderId'
                        )
                )
                ->setParameter(
                    'orderId',
                    $request->get('order_id')
                );
        }

        if (!empty($request->get('subscription_id'))) {
            $aliasSubscriptionPayment = 'sp';
            $qb->join(
                $alias . '.subscriptionPayment',
                $aliasSubscriptionPayment
            )
                ->where(
                    $qb->expr()
                        ->eq(
                            'IDENTITY(' . $aliasSubscriptionPayment . '.subscription)',
                            ':subscriptionId'
                        )
                )
                ->setParameter(
                    'subscriptionId',
                    $request->get('subscription_id')
                );
        }

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }

    public function getAllSuccessfulBetweenDates(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(['p', 'pt', 'op', 'sp'])
            ->from(Payment::class, 'p')
            ->leftJoin('p.paymentTaxes', 'pt')
            ->leftJoin('p.orderPayment', 'op')
            ->leftJoin('p.subscriptionPayment', 'sp')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    public function getOrdersProductsData(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(
            [
                'o.id as orderId',
                'o.totalDue',
                'o.productDue',
                'o.taxesDue',
                'o.shippingDue',
                'o.financeDue',
                'o.totalPaid',
                'oi.id as orderItemId',
                'oi.quantity',
                'oi.finalPrice',
                'pr.id as productId',
                'pr.sku as productSku',
                'pr.name as productName',
                'pr.type as productType',
            ]
        )
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->join('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'pr')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->where(
                $qb->expr()
                    ->between('o.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->orderBy('o.id', 'ASC')
            ->addOrderBy('oi.id', 'ASC')
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        return $qb->getQuery()->getResult();
    }

    public function getSubscriptionsProductsData(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(
            [
                'p.id as paymentId',
                's.id as subscriptionId',
                's.totalPrice',
                's.tax',
                'pr.id as productId',
                'pr.name as productName',
                'pr.sku as productSku',
            ]
        )
            ->from(Payment::class, 'p')
            ->join('p.subscriptionPayment', 'sp')
            ->join('sp.subscription', 's')
            ->leftJoin('s.product', 'pr')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->in('s.type', ':sub')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->in('p.type', ':renewal')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter(
                'sub',
                [
                    Subscription::TYPE_SUBSCRIPTION,
                    Subscription::TYPE_APPLE_SUBSCRIPTION,
                    Subscription::TYPE_GOOGLE_SUBSCRIPTION
                ]
            )
            ->setParameter(
                'renewal',
                [
                    Payment::TYPE_SUBSCRIPTION_RENEWAL,
                    Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL,
                    Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL
                ]
            )
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        return $qb->getQuery()->getResult();
    }

    public function getPaymentPlansProductsData(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(
            [
                's.id as subscriptionId',
                's.totalPrice',
                'o.id as orderId',
                'o.totalDue',
                'o.productDue',
                'o.taxesDue',
                'o.shippingDue',
                'o.financeDue',
                'o.totalPaid',
                'oi.id as orderItemId',
                'oi.quantity',
                'oi.finalPrice',
                'pr.id as productId',
                'pr.name as productName',
                'pr.sku as productSku',
            ]
        )
            ->from(Payment::class, 'p')
            ->join('p.subscriptionPayment', 'sp')
            ->join('sp.subscription', 's')
            ->join('s.order', 'o')
            ->join('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'pr')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.brand', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.type', ':pp')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('pp', Subscription::TYPE_PAYMENT_PLAN)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Carbon $smallDate
     * @param Carbon $bigDate
     * @param string $brand
     *
     * @return float
     */
    public function getPaymentsTaxPaid(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(o.taxesDue)')
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        $ordersTax = $qb->getQuery()->getSingleScalarResult();

        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(s.tax)')
            ->from(Payment::class, 'p')
            ->join('p.subscriptionPayment', 'sp')
            ->join('sp.subscription', 's')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.type', ':renewal')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('s.type', ':sub')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('renewal', Payment::TYPE_SUBSCRIPTION_RENEWAL)
            ->setParameter('sub', Subscription::TYPE_SUBSCRIPTION)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        $subscriptionsTax = $qb->getQuery()->getSingleScalarResult();

        return $ordersTax + $subscriptionsTax;
    }

    /**
     * @param Carbon $smallDate
     * @param Carbon $bigDate
     * @param string $brand
     *
     * @return float
     */
    public function getPaymentsShippingPaid(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(o.shippingDue) as shippingTotal')
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        /*
        suming up rounded order's shipping due will not ensure same result as PHP rounded 'order items ratio' * order shipping due
        query to test:
        SELECT SUM(ROUND(e0_.shipping_due, 2)) AS sclr_0 FROM ecommerce_payments e1_ INNER JOIN ecommerce_order_payments e2_ ON e1_.id = e2_.payment_id INNER JOIN ecommerce_orders e0_ ON e2_.order_id = e0_.id WHERE (e1_.created_at BETWEEN '2019-12-01 00:00:00' AND '2019-12-31 23:59:59') AND e1_.gateway_name = 'drumeo' AND e1_.status <> 'failed'
        */

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Carbon $smallDate
     * @param Carbon $bigDate
     * @param string $brand
     *
     * @return float
     */
    public function getPaymentsFinancePaid(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(o.financeDue)')
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Carbon $smallDate
     * @param Carbon $bigDate
     * @param string $brand
     *
     * @return float
     */
    public function getPaymentsNetProduct(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(oi.finalPrice)')
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->join('o.orderItems', 'oi')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        $ordersProductsDue = $qb->getQuery()->getSingleScalarResult();

        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(s.totalPrice - s.tax)')
            ->from(Payment::class, 'p')
            ->join('p.subscriptionPayment', 'sp')
            ->join('sp.subscription', 's')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                    // either subscriptions or payment plans, not initial order
                        $qb->expr()
                            ->andX(
                            // subscription type payment plan & payment type payment plan
                                $qb->expr()
                                    ->eq('s.type', ':pp'),
                                $qb->expr()
                                    ->eq('p.type', ':ppp')
                            ),
                        $qb->expr()
                            ->andX(
                            // subscription type subscription & payment type renewal
                                $qb->expr()
                                    ->eq('s.type', ':sub'),
                                $qb->expr()
                                    ->eq('p.type', ':renewal')
                            )
                    )
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('pp', Subscription::TYPE_PAYMENT_PLAN)
            ->setParameter('ppp', Payment::TYPE_PAYMENT_PLAN)
            ->setParameter('sub', Subscription::TYPE_SUBSCRIPTION)
            ->setParameter('renewal', Payment::TYPE_SUBSCRIPTION_RENEWAL)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        $subscriptionsProductsDue = $qb->getQuery()->getSingleScalarResult();

        return $ordersProductsDue + $subscriptionsProductsDue;
    }

    /**
     * Returns the total SUM of payment.totalPaid of non-failed TYPE_INITIAL_ORDER payments of specified day
     *
     * @param Carbon $day
     * @param string $brand
     *
     * @return float
     */
    public function getDailyTotalSalesStats(Carbon $day, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(p.totalPaid) as totalSales')
            ->from(Payment::class, 'p')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere($qb->expr()->neq('p.status', ':failed'))
            ->setParameter('smallDateTime', $day)
            ->setParameter('bigDateTime', $day->copy()->addDay())
            ->setParameter('failed', Payment::STATUS_FAILED);

        if ($brand) {
            $qb->leftJoin('p.orderPayment', 'op')
                ->leftJoin('op.order', 'o')
                ->leftJoin('p.subscriptionPayment', 'sp')
                ->leftJoin('sp.subscription', 's')
                ->andWhere(
                    $qb->expr()
                        ->andX(
                            $qb->expr()
                                ->orX(
                                    $qb->expr()
                                        ->isNull('o'),
                                    $qb->expr()
                                        ->eq('o.brand', ':orderBrand')
                                ),
                            $qb->expr()
                                ->orX(
                                    $qb->expr()
                                        ->isNull('s'),
                                    $qb->expr()
                                        ->eq('s.brand', ':subscriptionBrand')
                                )
                        )
                )
                ->setParameter('orderBrand', $brand)
                ->setParameter('subscriptionBrand', $brand);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the total number of orders that have non-failed TYPE_INITIAL_ORDER payments of specified day
     *
     * @param Carbon $day
     * @param string $brand
     *
     * @return int
     */
    public function getDailyTotalOrdersStats(Carbon $day, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('COUNT(p.id) as totalOrders')
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere($qb->expr()->neq('p.status', ':failed'))
            ->setParameter('smallDateTime', $day)
            ->setParameter('bigDateTime', $day->copy()->addDay())
            ->setParameter('failed', Payment::STATUS_FAILED);

        if ($brand) {
            $qb->andWhere(
                $qb->expr()
                    ->eq('o.brand', ':brand')
            )
                ->setParameter('brand', $brand);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the total SUM of payment.totalPaid of non-failed TYPE_SUBSCRIPTION_RENEWAL payments of specified day
     *
     * @param Carbon $day
     * @param string $brand
     *
     * @return float
     */
    public function getDailyTotalSalesFromRenewals(Carbon $day, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(p.totalPaid) as totalRenewals')
            ->from(Payment::class, 'p')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere($qb->expr()->eq('p.type', ':renew'))
            ->andWhere($qb->expr()->neq('p.status', ':failed'))
            ->setParameter('smallDateTime', $day)
            ->setParameter('bigDateTime', $day->copy()->addDay())
            ->setParameter('renew', Payment::TYPE_SUBSCRIPTION_RENEWAL)
            ->setParameter('failed', Payment::STATUS_FAILED);

        if ($brand) {
            $qb->leftJoin('p.subscriptionPayment', 'sp')
                ->leftJoin('sp.subscription', 's')
                ->andWhere(
                    $qb->expr()
                        ->eq('s.brand', ':brand')
                )
                ->setParameter('brand', $brand);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the total number of non-failed TYPE_SUBSCRIPTION_RENEWAL payments of specified day
     *
     * @param Carbon $day
     * @param string $brand
     *
     * @return int
     */
    public function getDailyTotalSuccessfulRenewals(Carbon $day, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('COUNT(p.id) as totalRenewals')
            ->from(Payment::class, 'p')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere($qb->expr()->eq('p.type', ':renew'))
            ->andWhere($qb->expr()->neq('p.status', ':failed'))
            ->setParameter('smallDateTime', $day)
            ->setParameter('bigDateTime', $day->copy()->addDay())
            ->setParameter('renew', Payment::TYPE_SUBSCRIPTION_RENEWAL)
            ->setParameter('failed', Payment::STATUS_FAILED);

        if ($brand) {
            $qb->leftJoin('p.subscriptionPayment', 'sp')
                ->leftJoin('sp.subscription', 's')
                ->andWhere(
                    $qb->expr()
                        ->eq('s.brand', ':brand')
                )
                ->setParameter('brand', $brand);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the total number of failed TYPE_SUBSCRIPTION_RENEWAL payments of specified day
     *
     * @param Carbon $day
     * @param string $brand
     *
     * @return int
     */
    public function getDailyTotalFailedRenewals(Carbon $day, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('COUNT(p.id) as totalRenewals')
            ->from(Payment::class, 'p')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere($qb->expr()->eq('p.type', ':renew'))
            ->andWhere($qb->expr()->eq('p.status', ':failed'))
            ->setParameter('smallDateTime', $day)
            ->setParameter('bigDateTime', $day->copy()->addDay())
            ->setParameter('renew', Payment::TYPE_SUBSCRIPTION_RENEWAL)
            ->setParameter('failed', Payment::STATUS_FAILED);

        if ($brand) {
            $qb->leftJoin('p.subscriptionPayment', 'sp')
                ->leftJoin('sp.subscription', 's')
                ->andWhere(
                    $qb->expr()
                        ->eq('s.brand', ':brand')
                )
                ->setParameter('brand', $brand);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the aggregated order items's products, of non-failed paid orders,
     *   each with SUM(oreder_item.finalPrice) AS sales and COUNT(product.id) AS sold
     *   grouped by product.id
     *
     * @param Carbon $day
     * @param string $brand
     *
     * @return array
     */
    public function getDailyOrdersProductStatistic(Carbon $day, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(['p', 'op', 'o', 'oi', 'pr'])
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'pr')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere($qb->expr()->neq('p.status', ':failed'))
            ->setParameter('smallDateTime', $day)
            ->setParameter('bigDateTime', $day->copy()->addDay())
            ->setParameter('failed', Payment::STATUS_FAILED);

        if ($brand) {
            $qb->andWhere(
                $qb->expr()
                    ->eq('o.brand', ':brand')
            )
                ->setParameter('brand', $brand);
        }

        /**
         * @var $results Payment[]
         */
        $results = $qb->getQuery()->getResult();

        // We must set the price per each order item properly based on how much was actually paid in the payment.
        // There is a bug where payment plan renewal payments are being counted at full order value, causing large over
        // reporting. For example the 2nd payment on an order for a $1000 lifetime product (with a 5 payment plan), will
        // report $1000 paid for EACH payment, instead of $200. This is because we are using the order item final price.
        // We'll use the ratios of paid to total due and payment plan to fix this.

        // this is the final return format required for this func:
//        ^ array:27 [
//          0 => array:4 [
//              "id" => 77
//              "sku" => "DSYS2-DIGI"
//              "sales" => "338.00"
//              "sold" => "130"
//          ]
//          1 => array:4 [
//              "id" => 86
//              "sku" => "SD-DIGI"
//              "sales" => "94.00"
//              "sold" => "128"
//          ]
//        ]

        $returnArray = [];

        foreach ($results as $result) {
            $totalPaid = $result->getTotalPaid();

            // spread the total paid across each order item based on their ratio
            $sumOfAllOrderItemsFinalPrice = 0;

            foreach ($result->getOrder()->getOrderItems() as $orderItem) {
                $sumOfAllOrderItemsFinalPrice += $orderItem->getFinalPrice();
            }

            foreach ($result->getOrder()->getOrderItems() as $orderItem) {
                if ($sumOfAllOrderItemsFinalPrice * $totalPaid > 0) {
                    $paidForThisOrderItem =
                        round($orderItem->getFinalPrice() / $sumOfAllOrderItemsFinalPrice * $totalPaid, 2);
                } else {
                    $paidForThisOrderItem = 0;
                }

                $returnArray[$orderItem->getProduct()->getId()]['id'] = $orderItem->getProduct()->getId();
                $returnArray[$orderItem->getProduct()->getId()]['sku'] = $orderItem->getProduct()->getSku();

                if (empty($returnArray[$orderItem->getProduct()->getId()]['sales'])) {
                    $returnArray[$orderItem->getProduct()->getId()]['sales'] = 0;
                }

                $returnArray[$orderItem->getProduct()->getId()]['sales'] += $paidForThisOrderItem;

                if (empty($returnArray[$orderItem->getProduct()->getId()]['sold'])) {
                    $returnArray[$orderItem->getProduct()->getId()]['sold'] = 0;
                }

                if ($result->getType() == Payment::TYPE_INITIAL_ORDER) {
                    $returnArray[$orderItem->getProduct()->getId()]['sold'] += 1;
                }
            }

        }

        return $returnArray;
    }

    /**
     * Returns the aggregated subscription's products, with non-failed TYPE_SUBSCRIPTION_RENEWAL payments,
     *   each with SUM(subscription.totalPrice) AS sales and COUNT(product.id) AS sold
     *   grouped by product.id
     *
     * @param Carbon $day
     * @param string $brand
     *
     * @return array
     */
    public function getDailySubscriptionsProductStatistic(Carbon $day, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(['pr.id', 'pr.sku', 'SUM(s.totalPrice) AS sales', 'COUNT(pr.id) AS sold'])
            ->from(Payment::class, 'p')
            ->join('p.subscriptionPayment', 'sp')
            ->join('sp.subscription', 's')
            ->join('s.product', 'pr')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere($qb->expr()->eq('s.type', ':subscription'))
            ->andWhere($qb->expr()->eq('p.type', ':renew'))
            ->andWhere($qb->expr()->neq('p.status', ':failed'))
            ->groupBy('pr.id')
            ->setParameter('smallDateTime', $day)
            ->setParameter('bigDateTime', $day->copy()->addDay())
            ->setParameter('subscription', Subscription::TYPE_SUBSCRIPTION)
            ->setParameter('renew', Payment::TYPE_SUBSCRIPTION_RENEWAL)
            ->setParameter('failed', Payment::STATUS_FAILED);

        if ($brand) {
            $qb->andWhere(
                $qb->expr()
                    ->eq('s.brand', ':brand')
            )
                ->setParameter('brand', $brand);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns order payments with associated payments
     *
     * @param Order $order
     *
     * @return array
     */
    public function getOrderPayments(Order $order): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['op', 'p'])
            ->from(OrderPayment::class, 'op')
            ->join('op.payment', 'p')
            ->where(
                $qb->expr()
                    ->eq('op.order', ':order')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('p.deletedAt')
            );

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('order', $order);

        return $q->getResult();
    }

    /**
     * Returns subscription payments
     *
     * @param Subscription $subscription
     *
     * @return array
     */
    public function getSubscriptionPayments(Subscription $subscription): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['p'])
            ->from(Payment::class, 'p')
            ->join(
                SubscriptionPayment::class,
                'sp',
                Join::WITH,
                $qb->expr()
                    ->eq(1, 1)
            )
            ->join('sp.payment', 'pj')
            ->where('pj.id = p.id')
            ->andWhere('sp.subscription = :subscription')
            ->andWhere(
                $qb->expr()
                    ->isNull('p.deletedAt')
            );

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('subscription', $subscription);

        return $q->getResult();
    }

    /**
     * Returns payments entities related to specified order
     *
     * @param Order $order
     *
     * @return array
     */
    public function getPaymentsByOrder(Order $order): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['p'])
            ->from(Payment::class, 'p')
            ->join(
                OrderPayment::class,
                'op',
                Join::WITH,
                $qb->expr()
                    ->eq(true, true)
            )
            ->join('op.payment', 'py')
            ->where(
                $qb->expr()
                    ->eq('op.order', ':order')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.id', 'py.id')
            )
            ->setParameter('order', $order);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Returns payment entity with related payment method
     *
     * @param int $paymentId
     *
     * @return Payment
     *
     * @throws NonUniqueResultException
     */
    public function getPaymentAndPaymentMethod(int $paymentId): ?Payment
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['p', 'pm'])
            ->from(Payment::class, 'p')
            ->leftJoin('p.paymentMethod', 'pm')
            ->where(
                $qb->expr()
                    ->eq('p.id', ':id')
            )
            ->setParameter('id', $paymentId);

        return $qb->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param $userId
     * @param bool $paidOnly
     * @param string $brand
     * @return Payment[]
     */
    public function getAllUsersPayments(
        $userId,
        $paidOnly = false,
        $brand = null
    )
    {
        if ($this->getEntityManager()
            ->getFilters()
            ->isEnabled('soft-deleteable')) {

            $this->getEntityManager()
                ->getFilters()
                ->disable('soft-deleteable');
        }

        $allPayments = [];

        // order payments
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        /** @var $ordersWithPayments Order[] */
        $qb->select('o', 'p', 'pm', 'op')
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->leftJoin('p.paymentMethod', 'pm')
            ->where('o.user = :userId')
            ->setParameter('userId', $userId);

        if ($paidOnly) {
            $qb->andWhere(
                $qb->expr()
                    ->gt('p.totalPaid', 0)
            );
        }

        if ($brand) {
            $qb->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
                ->setParameter('brand', $brand);
        }

        $payments =
            $qb->getQuery()
                ->getResult();

        foreach ($payments as $payment) {
            /** @var $payment Payment */
            $allPayments[$payment->getId()] = $payment;
        }

        // subscription payments
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        /** @var $subscriptionsWithPayments Subscription[] */
        $qb->select('s', 'p', 'pm', 'sp')
            ->from(Payment::class, 'p')
            ->join('p.subscriptionPayment', 'sp')
            ->join('sp.subscription', 's')
            ->leftJoin('p.paymentMethod', 'pm')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId);

        if ($paidOnly) {
            $qb->andWhere(
                $qb->expr()
                    ->gt('p.totalPaid', 0)
            );
        }

        if ($brand) {
            $qb->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
                ->setParameter('brand', $brand);
        }

        $payments =
            $qb->getQuery()
                ->getResult();

        foreach ($payments as $payment) {
            /** @var $payment Payment */
            $allPayments[$payment->getId()] = $payment;
        }

        if (!$this->getEntityManager()
            ->getFilters()
            ->isEnabled('soft-deleteable')) {

            $this->getEntityManager()
                ->getFilters()
                ->enable('soft-deleteable');
        }

        return $allPayments;
    }

    /**
     * @param string $externalId
     * @param string $externalProvider
     *
     * @return Payment|null
     *
     * @throws NonUniqueResultException
     */
    public function getByExternalIdAndProvider($externalId, $externalProvider)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['p'])
            ->from(Payment::class, 'p')
            ->where($qb->expr()->eq('p.externalId', ':externalId'))
            ->andWhere($qb->expr()->eq('p.externalProvider', ':externalProvider'))
            ->setParameter('externalId', $externalId)
            ->setParameter('externalProvider', $externalProvider);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
