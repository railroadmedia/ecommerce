<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Railroad\Ecommerce\Entities\Refund;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class RefundRepository
 *
 * @method Refund find($id, $lockMode = null, $lockVersion = null)
 * @method Refund findOneBy(array $criteria, array $orderBy = null)
 * @method Refund[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Refund[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class RefundRepository extends EntityRepository
{
    /**
     * RefundRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(Refund::class));
    }

    public function getPaymentsRefunds(array $payments): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['r'])
            ->from(Refund::class, 'r')
            ->where(
                $qb->expr()
                    ->in('r.payment', ':payments')
            )
            ->setParameter('payments', $payments);

        return $qb->getQuery()->getResult();
    }
}
