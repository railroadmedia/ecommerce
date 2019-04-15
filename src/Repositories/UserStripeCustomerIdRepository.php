<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\UserStripeCustomerId;

/**
 * Class UserStripeCustomerIdRepository
 * @package Railroad\Ecommerce\Repositories
 */
class UserStripeCustomerIdRepository extends RepositoryBase
{

    /**
     * @param int $userId
     *
     * @return UserStripeCustomerId|null
     */
    public function getByUserId(int $userId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('usci')
            ->from(UserStripeCustomerId::class, 'usci')
            ->where('usci.user = :userId')
            ->setParameter('userId', $userId);

        return $qb->getQuery()
            ->useResultCache($this->arrayCache)
            ->getOneOrNullResult();
    }
}
