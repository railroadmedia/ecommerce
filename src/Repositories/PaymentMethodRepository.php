<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\Query\Expr\Join;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class PaymentMethodRepository
 * @package Railroad\Ecommerce\Repositories
 */
class PaymentMethodRepository extends RepositoryBase
{
    /**
     * CreditCardRepository constructor.
     *
     * @param EcommerceEntityManager $entityManager
     */
    public function __construct(EcommerceEntityManager $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(PaymentMethod::class));
    }

    /**
     * @param int $id
     * @return PaymentMethod|null
     */
    public function byId(int $id): ?PaymentMethod
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select(['pm', 'cc', 'ppba'])
                ->from(PaymentMethod::class, 'pm')
                ->leftJoin('pm.creditCard', 'cc')
                ->leftJoin('pm.paypalBillingAgreement', 'ppba')
                ->where('pm.id = :id')
                ->getQuery()
                ->setParameter('id', $id)
                ->setResultCacheDriver($this->arrayCache);

        return $q->getOneOrNullResult();
    }

    /**
     * @param $userId
     * @param $paymentMethodId
     *
     * @return PaymentMethod|null
     */
    public function getUsersPaymentMethodById($userId, $paymentMethodId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['pm', 'cc', 'ppba'])
            ->from(PaymentMethod::class, 'pm')
            ->join(
                UserPaymentMethods::class,
                'upm',
                Join::WITH,
                $qb->expr()
                    ->eq(1, 1)
            )
            ->join('upm.paymentMethod', 'pmj')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba')
            ->where('upm.user = :userId')
            ->andWhere('pmj.id = pm.id')
            ->andWhere('pm.id = :paymentMethodId')
            ->setParameter('userId', $userId)
            ->setParameter('paymentMethodId', $paymentMethodId);

        return $qb->getQuery()
            ->useResultCache($this->arrayCache)
            ->getOneOrNullResult();
    }

    /**
     * @param $userId
     * @return PaymentMethod|null
     */
    public function getUsersPrimaryPaymentMethod($userId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select(['pm', 'cc', 'ppba'])
            ->from(PaymentMethod::class, 'pm')
            ->join(
                UserPaymentMethods::class,
                'upm',
                Join::WITH,
                $qb->expr()
                    ->eq(1, 1)
            )
            ->join('upm.paymentMethod', 'pmj')
            ->leftJoin('pm.creditCard', 'cc')
            ->leftJoin('pm.paypalBillingAgreement', 'ppba')
            ->where('upm.user = :userId')
            ->andWhere('pmj.id = pm.id')
            ->andWhere('upm.isPrimary = true')
            ->setParameter('userId', $userId);

        return $qb->getQuery()
            ->useResultCache($this->arrayCache)
            ->getOneOrNullResult();
    }

    /**
     * @param $userId
     * @return PaymentMethod[]
     */
    public function getAllUsersPaymentMethods($userId)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $paymentMethods =
            $qb->select(['upm', 'pm', 'cc', 'ppba'])
                ->from(PaymentMethod::class, 'pm')
                ->join('pm.userPaymentMethod', 'upm')
                ->leftJoin('pm.creditCard', 'cc')
                ->leftJoin('pm.paypalBillingAgreement', 'ppba')
                ->where(
                    $qb->expr()
                        ->eq('upm.user', ':user')
                )
                ->setParameter('user', $userId)
                ->getQuery()
                ->getResult();

        return $paymentMethods;
    }
}
