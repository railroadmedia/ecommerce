<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderPayment;

class PaymentRepository extends EntityRepository
{
    public function getOrderPayments(Order $order)
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select(['op', 'p'])
            ->from(OrderPayment::class, 'op')
            ->join('op.payment', 'p')
            ->where($qb->expr()->eq('op.order', ':order'))
            ->andWhere($qb->expr()->isNull('p.deletedOn'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('order', $order);

        return $q->getResult();
    }
}
