<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Contracts\UserInterface;
use Railroad\Ecommerce\Entities\UserPaymentMethods;

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
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(UserPaymentMethods::class));
    }

    /**
     * Returns users' primary payment method
     *
     * @param UserInterface $user
     *
     * @return UserPaymentMethods
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUserPrimaryPaymentMethod(
        UserInterface $user
    ): ?UserPaymentMethods {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select('p')
            ->from($this->getClassName(), 'p')
            ->where($qb->expr()->in('p.user', ':user'))
            ->andWhere($qb->expr()->in('p.isPrimary', ':true'));

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q
            ->setParameter('user', $user)
            ->setParameter('true', true);

        return $q->getOneOrNullResult();
    }
}
