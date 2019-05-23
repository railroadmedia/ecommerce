<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class UserPaymentMethodsRepository
 *
 * @method UserPaymentMethods find($id, $lockMode = null, $lockVersion = null)
 * @method UserPaymentMethods findOneBy(array $criteria, array $orderBy = null)
 * @method UserPaymentMethods[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method UserPaymentMethods[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class UserPaymentMethodsRepository extends EntityRepository
{
    /**
     * UserPaymentMethodsRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(UserPaymentMethods::class));
    }

    /**
     * Returns users' primary payment method
     *
     * @param User $user
     *
     * @return UserPaymentMethods
     *
     * @throws NonUniqueResultException
     */
    public function getUserPrimaryPaymentMethod(User $user): ?UserPaymentMethods
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('p')
            ->from($this->getClassName(), 'p')
            ->where(
                $qb->expr()
                    ->in('p.user', ':user')
            )
            ->andWhere(
                $qb->expr()
                    ->in('p.isPrimary', ':true')
            );

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('user', $user)
            ->setParameter('true', true);

        return $q->getOneOrNullResult();
    }

    /**
     * @param int $paymentMethodId
     *
     * @return UserPaymentMethods
     *
     * @throws NonUniqueResultException
     */
    public function getByMethodId(int $paymentMethodId): ?UserPaymentMethods
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('p')
            ->from($this->getClassName(), 'p')
            ->where(
                $qb->expr()
                    ->eq('IDENTITY(p.paymentMethod)', ':id')
            )
            ->setParameter('id', $paymentMethodId);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
