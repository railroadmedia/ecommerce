<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Railroad\Ecommerce\Entities\MembershipStats;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;

/**
 * Class MembershipStatsRepository
 *
 * @method MembershipStats find($id, $lockMode = null, $lockVersion = null)
 * @method MembershipStats findOneBy(array $criteria, array $orderBy = null)
 * @method MembershipStats[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method MembershipStats[] findAll()
 *
 * @package Railroad\Ecommerce\Repositories
 */
class MembershipStatsRepository extends EntityRepository
{
    /**
     * MembershipStatsRepository constructor.
     *
     * @param EcommerceEntityManager $em
     */
    public function __construct(EcommerceEntityManager $em)
    {
        parent::__construct(
            $em,
            $em->getClassMetadata(MembershipStats::class)
        );
    }

    /**
     * @param Carbon $smallDateTime
     * @param Carbon $bigDateTime
     * @param string|null $intervalType
     * @param string|null $brand
     *
     * @return MembershipStats[]
     */
    public function getStats(
        Carbon $smallDateTime,
        Carbon $bigDateTime,
        ?string $intervalType = null,
        ?string $brand = null
    ) {
        $qb = $this->createQueryBuilder('s');

        $qb->where(
                $qb->expr()
                    ->between('s.statsDate', ':smallDate', ':bigDate')
            )
            ->orderBy('s.statsDate', 'DESC')
            ->setParameter('smallDate', $smallDateTime->toDateString())
            ->setParameter('bigDate', $bigDateTime->toDateString());

        if ($intervalType) {
            $qb->andWhere(
                    $qb->expr()
                        ->eq('s.intervalType', ':intervalType')
                )
                ->setParameter('intervalType', $intervalType);
        }

        if ($brand) {
            $qb->andWhere(
                    $qb->expr()
                        ->eq('s.brand', ':brand')
                )
                ->setParameter('brand', $brand);
        }

        return $qb->getQuery()
                    ->getResult();
    }
}
