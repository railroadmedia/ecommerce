<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\UserStripeCustomerId;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class UserStripeCustomerIdRepository
 * @package Railroad\Ecommerce\Repositories
 */
class UserStripeCustomerIdRepository extends RepositoryBase
{
    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(UserStripeCustomerId::class));
    }

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
            ->setParameter('userId', $userId)
            ->orderBy('usci.id', 'DESC');

        $result = $qb->getQuery()// ->useResultCache($this->arrayCache)
            ->getResult();

        if ($result) {
            return $result[0];
        }
    }
}
