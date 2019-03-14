<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\ShippingCostsWeightRange;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class ShippingCostsWeightRangeRepository
 *
 * @method ShippingCostsWeightRange find($id, $lockMode = null, $lockVersion = null)
 * @method ShippingCostsWeightRange findOneBy(array $criteria, array $orderBy = null)
 * @method ShippingCostsWeightRange[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method ShippingCostsWeightRange[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class ShippingCostsWeightRangeRepository extends EntityRepository
{
    /**
     * ShippingCostsWeightRangeRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(ShippingCostsWeightRange::class)
        );
    }
}
