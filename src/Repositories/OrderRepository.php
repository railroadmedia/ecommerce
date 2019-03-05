<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Address;

class OrderRepository extends EntityRepository
{
    public function ordersWithAdressExist(Address $address)
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select('COUNT(o)')
            ->from($this->getClassName(), 'o')
            ->where($qb->expr()->eq('o.shippingAddress', ':address'))
            ->orWhere($qb->expr()->eq('o.billingAddress', ':address'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('address', $address);

        return (integer) $q->getSingleScalarResult() > 0;
    }
}
