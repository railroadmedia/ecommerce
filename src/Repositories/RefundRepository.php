<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Refund;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class RefundRepository
 *
 * @method Refund find($id, $lockMode = null, $lockVersion = null)
 * @method Refund findOneBy(array $criteria, array $orderBy = null)
 * @method Refund[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Refund[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class RefundRepository extends EntityRepository
{
    /**
     * RefundRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Refund::class));
    }

    public function getAccountingOrderProductsData(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(
                [
                    'r.id as refundId',
                    'r.refundedAmount',
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
            ->from(Refund::class, 'r')
            ->join('r.payment', 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->join('o.orderItems', 'oi')
            ->join('oi.product', 'pr')
            ->where(
                $qb->expr()
                    ->between('r.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->orderBy('o.id', 'ASC')
            ->addOrderBy('oi.id', 'ASC')
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand);

        return $qb->getQuery()->getResult();
    }

    public function getAccountingSubscriptionProductsData(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(
                [
                    'r.id as refundId',
                    'r.paymentAmount',
                    'r.refundedAmount',
                    's.id as subscriptionId',
                    's.totalPrice',
                    's.tax',
                    'pr.id as productId',
                    'pr.name as productName',
                    'pr.sku as productSku',
                ]
            )
            ->from(Refund::class, 'r')
            ->join('r.payment', 'p')
            ->join('p.subscriptionPayment', 'sp')
            ->join('sp.subscription', 's')
            ->join('s.product', 'pr')
            ->where(
                $qb->expr()
                    ->between('r.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.type', ':renewal')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('renewal', Payment::TYPE_SUBSCRIPTION_RENEWAL);

        return $qb->getQuery()->getResult();
    }

    public function getBrandRefunds(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(
                [
                    'r.id as refundId',
                    'r.refundedAmount',
                ]
            )
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

        return $qb->getQuery()->getResult();
    }

    public function getAccountingPaymentPlansProductsData(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select(
                [
                    'r.id as refundId',
                    'r.paymentAmount',
                    'r.refundedAmount',
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
            ->from(Refund::class, 'r')
            ->join('r.payment', 'p')
            ->join('p.subscriptionPayment', 'sp')
            ->join('sp.subscription', 's')
            ->join('s.order', 'o')
            ->join('o.orderItems', 'oi')
            ->join('oi.product', 'pr')
            ->where(
                $qb->expr()
                    ->between('r.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.gatewayName', ':brand')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.type', ':pp')
            )
            ->setParameter('smallDateTime', $smallDate)
            ->setParameter('bigDateTime', $bigDate)
            ->setParameter('brand', $brand)
            ->setParameter('pp', Payment::TYPE_PAYMENT_PLAN);

        return $qb->getQuery()->getResult();
    }

    public function getPaymentsRefunds(array $payments): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['r'])
            ->from(Refund::class, 'r')
            ->where(
                $qb->expr()
                    ->in('r.payment', ':payments')
            )
            ->setParameter('payments', $payments);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the total SUM of refund.refundedAmount of specified day
     *
     * @param string $day
     * @param string $brand
     *
     * @return float
     */
    public function getDailyTotalRefunds($day, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('SUM(r.refundedAmount) as totalRefunds')
            ->from(Refund::class, 'r')
            ->where(
                $qb->expr()
                    ->between('r.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->setParameter('smallDateTime', $day)
            ->setParameter('bigDateTime', $day->copy()->endOfDay());

        if ($brand) {
            $qb->join('r.payment', 'p')
                ->leftJoin('p.orderPayment', 'op')
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

        return
            $qb->getQuery()
                ->getSingleScalarResult();
    }

    /**
     * Returns the total SUM of payments refunded amount
     *
     * @param Carbon $smallDate
     * @param Carbon $bigDate
     * @param string $brand
     *
     * @return float
     */
    public function getRefundPaid(Carbon $smallDate, Carbon $bigDate, $brand)
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb = $qb->select('SUM(r.refundedAmount) as refundedTotal')
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

        return $qb->getQuery()->getSingleScalarResult();
    }
}
