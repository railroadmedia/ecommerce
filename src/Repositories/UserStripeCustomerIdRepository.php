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
     * @param $gateway
     * @return UserStripeCustomerId|null
     */
    public function getByUserId(int $userId, $gateway)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('usci')
            ->from(UserStripeCustomerId::class, 'usci')
            ->where('usci.user = :userId')
            ->andWhere('usci.paymentGatewayName = :paymentGatewayName')
            ->setParameter('userId', $userId)
            ->setParameter('paymentGatewayName', $gateway)
            ->orderBy('usci.id', 'DESC');

        $result = $qb->getQuery()// ->useResultCache($this->arrayCache)
            ->getResult();

        if ($result) {
            return $result[0] ?? null;
        }

        return null;
    }
}
