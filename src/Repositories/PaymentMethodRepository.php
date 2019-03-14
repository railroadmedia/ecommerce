<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class PaymentMethodRepository
 *
 * @method PaymentMethod find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentMethod findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentMethod[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method PaymentMethod[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class PaymentMethodRepository extends EntityRepository
{
    /**
     * PaymentMethodRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(PaymentMethod::class));
    }
}
