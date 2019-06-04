<?php

namespace Railroad\Ecommerce\Repositories;

use Doctrine\ORM\QueryBuilder;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class OrderPaymentRepository
 *
 * @method OrderPayment find($id, $lockMode = null, $lockVersion = null)
 * @method OrderPayment findOneBy(array $criteria, array $orderBy = null)
 * @method OrderPayment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method OrderPayment[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class OrderPaymentRepository extends RepositoryBase
{
    /**
     * OrderPaymentRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct($em, $em->getClassMetadata(OrderPayment::class));
    }

    /**
     * @param Payment $payment
     *
     * @return OrderPayment[]
     */
    public function getByPayment(Payment $payment): array
    {
        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['op', 'p'])
            ->from(OrderPayment::class, 'op')
            ->join('op.payment', 'p')
            ->where(
                $qb->expr()
                    ->eq('op.payment', ':payment')
            )
            ->andWhere(
                $qb->expr()
                    ->isNull('p.deletedOn')
            )
            ->setParameter('payment', $payment);

        return $qb->getQuery()->getResult();
    }
}
