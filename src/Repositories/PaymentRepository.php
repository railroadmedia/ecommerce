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
use Railroad\Ecommerce\Entities\Subscription;
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

    /**
     * @param Request $request
     *
     * @return Payment[]
     */
    public function getPaymentsForStats(Request $request): array
    {
        $smallDate = $request->get(
            'small_date_time',
            Carbon::now()
                ->subDay()
                ->toDateTimeString()
        );

        $smallDateTime =
            Carbon::parse($smallDate)
                ->startOfDay();

        $bigDate = $request->get(
            'big_date_time',
            Carbon::now()
                ->subDay()
                ->toDateTimeString()
        );

        $bigDateTime =
            Carbon::parse($bigDate)
                ->endOfDay();

        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['p', 'op', 'o', 'oi', 'sp', 's'])
            ->from(Payment::class, 'p')
            ->leftJoin('p.orderPayment', 'op')
            ->leftJoin('op.order', 'o')
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('p.subscriptionPayment', 'sp')
            ->leftJoin('sp.subscription', 's')
            ->where(
                $qb->expr()
                    ->between('p.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->setParameter('smallDateTime', $smallDateTime)
            ->setParameter('bigDateTime', $bigDateTime);

        if ($request->has('brand')) {
            $qb->andWhere(
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
                ->setParameter('orderBrand', $request->get('brand'))
                ->setParameter('subscriptionBrand', $request->get('brand'));
        }

        return $qb->getQuery()
            ->getResult();
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
