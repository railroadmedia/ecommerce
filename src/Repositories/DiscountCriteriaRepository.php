<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\DiscountCriteria;

/**
 * Class DiscountCriteriaRepository
 *
 * @method DiscountCriteria find($id, $lockMode = null, $lockVersion = null)
 * @method DiscountCriteria findOneBy(array $criteria, array $orderBy = null)
 * @method DiscountCriteria[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method DiscountCriteria[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class DiscountCriteriaRepository extends EntityRepository
{
    /**
     * DiscountCriteriaRepository constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(DiscountCriteria::class));
    }
}
