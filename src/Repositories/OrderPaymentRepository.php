<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class OrderPaymentRepository
 *
 * @method OrderPayment find($id, $lockMode = null, $lockVersion = null)
 * @method OrderPayment findOneBy(array $criteria, array $orderBy = null)
 * @method OrderPayment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method OrderPayment[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class OrderPaymentRepository extends EntityRepository
{
    /**
     * OrderPaymentRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(OrderPayment::class));
    }
}
