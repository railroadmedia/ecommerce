<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\CustomerPaymentMethods;
use Railroad\Ecommerce\Entities\Customer;

class CustomerPaymentMethodsRepository extends EntityRepository
{
    public function getCustomerPrimaryPaymentMethod(Customer $customer): ?CustomerPaymentMethods
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select('p')
            ->from($this->getClassName(), 'p')
            ->where($qb->expr()->in('p.customer', ':customer'))
            ->andWhere($qb->expr()->in('p.isPrimary', ':true'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q
            ->setParameter('customer', $customer)
            ->setParameter('true', true);

        return $q->getOneOrNullResult();
    }
}
