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
        $payments = [];

        // order payments
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        /**
         * @var $ordersWithPayments Order[]
         */
        $qb->select('o', 'p', 'pm')
            ->from(Order::class, 'o')
            ->join('o.payments', 'p')
            ->join('p.paymentMethod', 'pm')
            ->where('o.user = :userId')
            ->setParameter('userId', $userId);

        if ($paidOnly) {
            $qb->andWhere(
                $qb->expr()
                    ->gt('p.totalPaid', 0)
            );
        }

        $ordersWithPayments =
            $qb->getQuery()
                ->getResult();

        foreach ($ordersWithPayments as $orderWithPayments) {
            foreach ($orderWithPayments->getPayments() as $payment) {
                $payments[$payment->getId()] = $payment;
            }
        }

        // subscription payments
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        /**
         * @var $subscriptionsWithPayments Subscription[]
         */
        $qb->select('s', 'p', 'pm')
            ->from(Subscription::class, 's')
            ->join('s.payments', 'p')
            ->join('p.paymentMethod', 'pm')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId);

        if ($paidOnly) {
            $qb->andWhere(
                $qb->expr()
                    ->gt('p.totalPaid', 0)
            );
        }

        $subscriptionsWithPayments =
            $qb->getQuery()
                ->getResult();

        foreach ($subscriptionsWithPayments as $subscriptionWithPayments) {
            foreach ($subscriptionWithPayments->getPayments() as $payment) {
                $payments[$payment->getId()] = $payment;
            }
        }

        return $payments;
    }
}
