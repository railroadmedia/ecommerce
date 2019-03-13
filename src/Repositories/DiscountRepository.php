<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Discount;

/**
 * Class DiscountRepository
 *
 * @method Discount find($id, $lockMode = null, $lockVersion = null)
 * @method Discount findOneBy(array $criteria, array $orderBy = null)
 * @method Discount[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Discount[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class DiscountRepository extends EntityRepository
{
    /**
     * DiscountRepository constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Discount::class));
    }
}
