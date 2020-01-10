<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Refund;
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
     * @throws \Doctrine\ORM\NoResultException
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
                    'pr.weight as productWeight',
                ]
            )
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->join('o.orderItems', 'oi')
            ->join('oi.product', 'pr')
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
            ->join('s.product', 'pr')
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
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('renewal', Payment::TYPE_SUBSCRIPTION_RENEWAL)
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
                    'oi.id as orderItemId',
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
            ->join('oi.product', 'pr')
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
                    ->eq('p.type', ':pp')
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('pp', Payment::TYPE_PAYMENT_PLAN)
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
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('renewal', Payment::TYPE_SUBSCRIPTION_RENEWAL)
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
                        $qb->expr()
                            ->eq('p.type', ':renewal'),
                        $qb->expr()
                            ->eq('p.type', ':pp')
                    )
            )
            ->andWhere(
                $qb->expr()
                    ->neq('p.status', ':notFailed')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('renewal', Payment::TYPE_SUBSCRIPTION_RENEWAL)
            ->setParameter('pp', Payment::TYPE_PAYMENT_PLAN)
            ->setParameter('notFailed', Payment::STATUS_FAILED);

        $subscriptionsProductsDue = $qb->getQuery()->getSingleScalarResult();

        return $ordersProductsDue + $subscriptionsProductsDue;
    }

    /**
     * @param Carbon $smallDate
     * @param Carbon $bigDate
     * @param string $brand
     *
     * @return float
     */
    public function getPaymentsNetPaid(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(p.totalPaid)')
            ->from(Payment::class, 'p')
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

        $paid = $qb->getQuery()->getSingleScalarResult();

        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(r.refundedAmount)')
            ->from(Refund::class, 'r')
            ->join('r.payment', 'p')
            ->where(
                $qb->expr()
                    ->between('r.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand);

        $refunded = $qb->getQuery()->getSingleScalarResult();

        // dd($paid); // 6879.23 - not matching
        // dd($refunded); // 1119.25 - matching

        return $paid - $refunded;
    }

    /**
     * Returns the total SUM of payment.totalPaid of non-failed TYPE_INITIAL_ORDER payments of specified day
     *
     * @param string $day
     * @param string $brand
     *
     * @return float
     */
    public function getDailyTotalSalesStats($day, $brand)
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
            ->setParameter('bigDateTime', $day->copy()->endOfDay())
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
     * @param string $day
     * @param string $brand
     *
     * @return int
     */
    public function getDailyTotalOrdersStats($day, $brand)
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
            ->setParameter('bigDateTime', $day->copy()->endOfDay())
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
     * @param string $day
     * @param string $brand
     *
     * @return float
     */
    public function getDailyTotalSalesFromRenewals($day, $brand)
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
            ->setParameter('bigDateTime', $day->copy()->endOfDay())
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
     * @param string $day
     * @param string $brand
     *
     * @return int
     */
    public function getDailyTotalSuccessfulRenewals($day, $brand)
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
            ->setParameter('bigDateTime', $day->copy()->endOfDay())
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
     * @param string $day
     * @param string $brand
     *
     * @return int
     */
    public function getDailyTotalFailedRenewals($day, $brand)
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
            ->setParameter('bigDateTime', $day->copy()->endOfDay())
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
     * @param string $day
     * @param string $brand
     *
     * @return array
     */
    public function getDailyOrdersProductStatistic($day, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(['pr.id', 'pr.sku', 'SUM(oi.finalPrice) AS sales', 'COUNT(pr.id) AS sold'])
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
            ->groupBy('pr.id')
            ->setParameter('smallDateTime', $day)
            ->setParameter('bigDateTime', $day->copy()->endOfDay())
            ->setParameter('failed', Payment::STATUS_FAILED);

        if ($brand) {
            $qb->andWhere(
                    $qb->expr()
                        ->eq('o.brand', ':brand')
                )
                ->setParameter('brand', $brand);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the aggregated subscription's products, with non-failed TYPE_SUBSCRIPTION_RENEWAL payments,
     *   each with SUM(subscription.totalPrice) AS sales and COUNT(product.id) AS sold
     *   grouped by product.id
     *
     * @param string $day
     * @param string $brand
     *
     * @return array
     */
    public function getDailySubscriptionsProductStatistic($day, $brand)
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
            ->setParameter('bigDateTime', $day->copy()->endOfDay())
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
}
