<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\AccessCode;

class ProductRepository extends EntityRepository
{
    public function getAccessCodeProducts(AccessCode $accessCode)
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
            ->where($qb->expr()->in('p.id', ':ids'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('ids', $accessCode->getProductIds());

        return $q->getResult();
    }
}
