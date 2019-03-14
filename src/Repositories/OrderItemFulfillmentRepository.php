<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class OrderItemFulfillmentRepository
 *
 * @method OrderItemFulfillment find($id, $lockMode = null, $lockVersion = null)
 * @method OrderItemFulfillment findOneBy(array $criteria, array $orderBy = null)
 * @method OrderItemFulfillment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method OrderItemFulfillment[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class OrderItemFulfillmentRepository extends EntityRepository
{
    /**
     * OrderItemFulfillmentRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(OrderItemFulfillment::class)
        );
    }
}
