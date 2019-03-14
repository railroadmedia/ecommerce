<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class SubscriptionPaymentRepository
 *
 * @method SubscriptionPayment find($id, $lockMode = null, $lockVersion = null)
 * @method SubscriptionPayment findOneBy(array $criteria, array $orderBy = null)
 * @method SubscriptionPayment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method SubscriptionPayment[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class SubscriptionPaymentRepository extends EntityRepository
{
    /**
     * SubscriptionPaymentRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(SubscriptionPayment::class)
        );
    }
}
