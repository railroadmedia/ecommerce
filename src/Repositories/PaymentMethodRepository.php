<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\Query\Expr\Join;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
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
        parent::__construct($entityManager, $entityManager->getClassMetadata(Payment::class));
    }
    /**
     * @param int $id
     * @return PaymentMethod|null
     */
    public function byId(int $id): ?PaymentMethod
    {
        $qb = $this->entityManager->createQueryBuilder();

        $q =
            $qb->select('pm')
                ->from(PaymentMethod::class, 'pm')
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

        $qb->select(['pm'])
            ->from(PaymentMethod::class, 'pm')
            ->join(
                UserPaymentMethods::class,
                'upm',
                Join::WITH,
                $qb->expr()
                    ->eq(1, 1)
            )
            ->join('upm.paymentMethod', 'pmj')
            ->where('upm.user = :userId')
            ->andWhere('pmj.id = pm.id')
            ->andWhere('pm.id = :paymentMethodId')
            ->setParameter('userId', $userId)
            ->setParameter('paymentMethodId', $paymentMethodId);

        return $qb->getQuery()
            ->useResultCache($this->arrayCache)
            ->getOneOrNullResult();
    }
}
