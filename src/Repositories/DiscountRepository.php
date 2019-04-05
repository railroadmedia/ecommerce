<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class DiscountRepository
 *
 * @method Discount find($id, $lockMode = null, $lockVersion = null)
 * @method Discount findOneBy(array $criteria, array $orderBy = null)
 * @method Discount[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Discount[] findByProduct(Product $product)
 * @method Discount[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class DiscountRepository extends EntityRepository
{
    /**
     * DiscountRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Discount::class));
    }

    /**
     * Returns an array of Discounts with associated DiscountCriteria loaded
     *
     * @return array
     */
    public function getActiveDiscountsWithCriteria()
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->getEntityManager()
            ->createQueryBuilder();

        $qb->select(['d', 'dc'])
            ->from($this->getClassName(), 'd')
            ->leftJoin('d.discountCriterias', 'dc')
            ->where($qb->expr()
                ->eq('d.active', ':active'))
            ->setParameter('active', true);

        return $qb->getQuery()
            ->getResult();
    }
}
