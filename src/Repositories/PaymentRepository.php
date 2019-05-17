<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class PaymentRepository
 *
 * @method Payment find($id, $lockMode = null, $lockVersion = null)
 * @method Payment findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Payment[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class PaymentRepository extends EntityRepository
{
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
     * Returns order payments with associated payments
     *
     * @param Order $order
     *
     * @return array
     */
    public function getOrderPayments(Order $order): array
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
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
                    ->isNull('p.deletedOn')
            );

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('order', $order);

        return $q->getResult();
    }

    /**
     * @param $userId
     * @param bool $paidOnly
     * @return Payment[]
     */
    public function getAllUsersPayments($userId, $paidOnly = false)
    {
        $allPayments = [];

        // order payments
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        /**
         * @var $ordersWithPayments Order[]
         */
        $qb->select('o', 'p', 'pm', 'op')
            ->from(Payment::class, 'p')
            ->join('p.orderPayment', 'op')
            ->join('op.order', 'o')
            ->join('p.paymentMethod', 'pm')
            ->where('o.user = :userId')
            ->setParameter('userId', $userId);

        if ($paidOnly) {
            $qb->andWhere(
                $qb->expr()
                    ->gt('p.totalPaid', 0)
            );
        }

        $payments =
            $qb->getQuery()
                ->getResult();

        foreach ($payments as $payment) {
            $allPayments[$payment->getId()] = $payment;
        }

        // subscription payments
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        /**
         * @var $subscriptionsWithPayments Subscription[]
         */
        $qb->select('s', 'p', 'pm', 'sp')
            ->from(Payment::class, 'p')
            ->join('p.subscriptionPayment', 'sp')
            ->join('sp.subscription', 's')
            ->join('p.paymentMethod', 'pm')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId);

        if ($paidOnly) {
            $qb->andWhere(
                $qb->expr()
                    ->gt('p.totalPaid', 0)
            );
        }

        $payments =
            $qb->getQuery()
                ->getResult();

        foreach ($payments as $payment) {
            $allPayments[$payment->getId()] = $payment;
        }

        return $allPayments;
    }
}
