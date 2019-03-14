<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class SubscriptionRepository
 *
 * @method Subscription find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Subscription[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class SubscriptionRepository extends EntityRepository
{
    /**
     * SubscriptionRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Subscription::class));
    }

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
