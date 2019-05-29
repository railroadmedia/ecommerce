<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
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

    /**
     * @param Request $request
     *
     * @return Order[]
     */
    public function getRefundsForStats(Request $request): array
    {
        $smallDateTime =
            $request->get(
                'small_date_time',
                Carbon::now()
                    ->subDay()
                    ->toDateTimeString()
            );

        $bigDateTime =
            $request->get(
                'big_date_time',
                Carbon::now()
                    ->subDay()
                    ->toDateTimeString()
            );

        /** @var $qb QueryBuilder */
        $qb =
            $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select(['r', 'p', 'op', 'o', 'sp', 's'])
            ->from(Refund::class, 'r')
            ->join('r.payment', 'p')
            ->leftJoin('p.orderPayment', 'op')
            ->leftJoin('op.order', 'o')
            ->leftJoin('p.subscriptionPayment', 'sp')
            ->leftJoin('sp.subscription', 's')
            ->where(
                $qb->expr()
                    ->between('r.createdAt', ':smallDateTime', ':bigDateTime')
            )
            ->setParameter('smallDateTime', $smallDateTime)
            ->setParameter('bigDateTime', $bigDateTime);

        if ($request->has('brand')) {
            $qb->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()->eq('o.brand', ':obrand'),
                        $qb->expr()->eq('s.brand', ':sbrand')
                    )
            )
                ->setParameter('obrand', $request->get('brand'))
                ->setParameter('sbrand', $request->get('brand'));
        }

        return
            $qb->getQuery()
                ->getResult();
    }
}
