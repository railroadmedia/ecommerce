<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\Query\Expr\Join;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\UserPaymentMethods;

/**
 * Class PaymentMethodRepository
 * @package Railroad\Ecommerce\Repositories
 */
class PaymentMethodRepository extends RepositoryBase
{
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
