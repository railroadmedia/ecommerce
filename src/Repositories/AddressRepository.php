<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\Address;

/**
 * Class AddressRepository
 * @package Railroad\Ecommerce\Repositories
 */
class AddressRepository extends RepositoryBase
{
    /**
     * @param $id
     * @return Address|null
     */
    public function byId($id)
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
