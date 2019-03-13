<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Address;

/**
 * Class AddressRepository
 *
 * @method Address find($id, $lockMode = null, $lockVersion = null)
 * @method Address findOneBy(array $criteria, array $orderBy = null)
 * @method Address[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Address[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class AddressRepository extends EntityRepository
{
    /**
     * AddressRepository constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Address::class));
    }
}
