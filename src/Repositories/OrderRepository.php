<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class OrderRepository
 *
 * @method Order find($id, $lockMode = null, $lockVersion = null)
 * @method Order findOneBy(array $criteria, array $orderBy = null)
 * @method Order[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Order[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class OrderRepository extends EntityRepository
{
    /**
     * OrderRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Order::class));
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Returns true if any order has billing or shipping $address set
     * The usage of select count() avoids NonUniqueResultException exception
     *
     * @param Address $address
     *
     * @return bool
     */
    public function ordersWithAdressExist(Address $address): bool
    {
        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select('COUNT(o)')
            ->from($this->getClassName(), 'o')
            ->where(
                $qb->expr()
                    ->eq('o.shippingAddress', ':address')
            )
            ->orWhere(
                $qb->expr()
                    ->eq('o.billingAddress', ':address')
            );

        /**
         * @var $q \Doctrine\ORM\Query
         */
        $q = $qb->getQuery();

        $q->setParameter('address', $address);

        return (integer)$q->getSingleScalarResult() > 0;
    }
}
