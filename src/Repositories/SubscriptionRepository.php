<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;

class SubscriptionRepository extends EntityRepository
{
    /**
     * Gets subscriptions that are related to the specified products
     *
     * @param array $products - array of product entities
     *
     * @return array
     */
    public function getProductsSubscriptions(array $products): array
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select('s')
            ->from($this->getClassName(), 's')
            ->where($qb->expr()->in('s.product', ':products'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('products', $products);

        return $q->getResult();
    }
}
