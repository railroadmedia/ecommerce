<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NonUniqueResultException;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Entities\CustomerPaymentMethods;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class CustomerPaymentMethodsRepository
 *
 * @method CustomerPaymentMethods find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerPaymentMethods findOneBy(array $criteria, array $orderBy = null)
 * @method CustomerPaymentMethods[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method CustomerPaymentMethods[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class CustomerPaymentMethodsRepository extends EntityRepository
{
    /**
     * CustomerPaymentMethodsRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(CustomerPaymentMethods::class)
        );
    }

    /**
     * Returns customers' primary payment method
     *
     * @param Customer $customer
     *
     * @return CustomerPaymentMethods
     *
     * @throws NonUniqueResultException
     */
    public function getCustomerPrimaryPaymentMethod(
        Customer $customer
    ): ?CustomerPaymentMethods
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('p')
            ->from($this->getClassName(), 'p')
            ->where(
                $qb->expr()
                    ->in('p.customer', ':customer')
            )
            ->andWhere(
                $qb->expr()
                    ->in('p.isPrimary', ':true')
            );

        /** @var $q Query */
        $q = $qb->getQuery();

        $q->setParameter('customer', $customer)
            ->setParameter('true', true);

        return $q->getOneOrNullResult();
    }
}
