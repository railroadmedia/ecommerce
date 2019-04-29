<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class AddressRepository
 * @package Railroad\Ecommerce\Repositories
 */
class AddressRepository extends RepositoryBase
{
    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(Address::class));
    }

    /**
     * @param $id
     * @return Address|null
     */
    public function byId($id): ?Address
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select('a')
                ->from(Address::class, 'a')
                ->where('a.id = :id')
                ->getQuery()
                ->setParameter('id', $id)
                ->setResultCacheDriver($this->arrayCache);

        return $q->getResult()[0] ?? null;
    }
}
