<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

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
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(DiscountCriteria::class));
    }
}
