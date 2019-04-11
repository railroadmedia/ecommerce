<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class UserStripeCustomerIdRepository
 *
 * @method UserProduct find($id, $lockMode = null, $lockVersion = null)
 * @method UserProduct findOneBy(array $criteria, array $orderBy = null)
 * @method UserProduct[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method UserProduct[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class UserStripeCustomerIdRepository extends EntityRepository
{
    /**
     * UserStripeCustomerIdRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(UserProduct::class));
    }
}
