<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class CustomerRepository
 *
 * @method Refund find($id, $lockMode = null, $lockVersion = null)
 * @method Refund findOneBy(array $criteria, array $orderBy = null)
 * @method Refund[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Refund[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class CustomerRepository extends EntityRepository
{
    /**
     * RefundRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Customer::class));
    }
}
