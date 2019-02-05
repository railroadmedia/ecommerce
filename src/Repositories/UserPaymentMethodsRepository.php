<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Usora\Entities\User;

class UserPaymentMethodsRepository extends EntityRepository
{
    public function getUserPrimaryPaymentMethod(User $user): ?UserPaymentMethods
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
            ->where($qb->expr()->in('p.user', ':user'))
            ->andWhere($qb->expr()->in('p.isPrimary', ':true'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q
            ->setParameter('user', $user)
            ->setParameter('true', true);

        return $q->getOneOrNullResult();
    }
}
